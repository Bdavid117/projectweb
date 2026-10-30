<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Models\TutorRequest;
use App\Support\Audit;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Consentimiento del estudiante para que un profesor lo acompañe como tutor: el profesor solicita, el estudiante
 * acepta o rechaza y puede dejar de compartir cuando quiera. Aceptar crea el vínculo (TutorAssignment) del periodo
 * vigente, que es lo que habilita al profesor a ver la información del estudiante. Cada paso queda en la bitácora
 * dentro de la misma transacción que el cambio.
 */
class TutorLinkWorkflow
{
    public function send(Request $http, Student $student, ?string $message): TutorRequest
    {
        $tutor = $http->user();

        if (AcademicTerm::current() === null) {
            throw new DomainException('No hay un periodo académico vigente configurado.');
        }

        if (TutorAssignment::isLinked($tutor, $student)) {
            throw new DomainException('Ya acompaña a este estudiante en el periodo vigente.');
        }

        if (TutorRequest::where('tutor_id', $tutor->id)->where('student_id', $student->id)->where('estado', TutorRequest::PENDING)->exists()) {
            throw new DomainException('Ya tiene una solicitud pendiente para este estudiante.');
        }

        try {
            return DB::transaction(function () use ($http, $tutor, $student, $message) {
                $request = TutorRequest::create([
                    'student_id' => $student->id,
                    'tutor_id' => $tutor->id,
                    'estado' => TutorRequest::PENDING,
                    'mensaje' => $message,
                ]);

                Audit::log($http, $request, 'tutoria.solicitud_enviada', [], ['student_id' => $student->id, 'tutor_id' => $tutor->id]);

                return $request;
            });
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('Ya tiene una solicitud pendiente para este estudiante.');
        }
    }

    public function accept(Request $http, TutorRequest $tutorRequest): void
    {
        try {
            DB::transaction(function () use ($http, $tutorRequest) {
                $locked = $this->lockPending($tutorRequest);
                $term = AcademicTerm::current() ?? throw new DomainException('No hay un periodo académico vigente configurado.');

                // Serializa dos aceptaciones simultáneas del mismo estudiante.
                Student::whereKey($locked->student_id)->lockForUpdate()->firstOrFail();

                $current = TutorAssignment::with('tutor')->where('student_id', $locked->student_id)->where('term_id', $term->id)->first();
                if ($current !== null) {
                    throw new DomainException('Ya comparte su información con '.$current->tutor->name.' en este periodo. Deje de compartir con esa persona antes de aceptar otra solicitud.');
                }

                $assignment = TutorAssignment::create(['student_id' => $locked->student_id, 'tutor_id' => $locked->tutor_id, 'term_id' => $term->id]);
                Audit::log($http, $assignment, 'tutoria.vinculo_creado', [], ['student_id' => $assignment->student_id, 'tutor_id' => $assignment->tutor_id, 'term_id' => $term->id]);
                Audit::update($http, $locked, ['estado' => TutorRequest::ACCEPTED, 'respondida_en' => now()], 'tutoria.solicitud_aceptada');
            });
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('Ya comparte su información con otra persona en este periodo. Deje de compartir con ella antes de aceptar otra solicitud.');
        }
    }

    public function reject(Request $http, TutorRequest $tutorRequest): void
    {
        DB::transaction(function () use ($http, $tutorRequest) {
            $locked = $this->lockPending($tutorRequest);
            Audit::update($http, $locked, ['estado' => TutorRequest::REJECTED, 'respondida_en' => now()], 'tutoria.solicitud_rechazada');
        });
    }

    /** El profesor retira una solicitud que el estudiante todavía no respondió. */
    public function cancel(Request $http, TutorRequest $tutorRequest): void
    {
        DB::transaction(function () use ($http, $tutorRequest) {
            $locked = $this->lockPending($tutorRequest);
            Audit::update($http, $locked, ['estado' => TutorRequest::CANCELLED, 'respondida_en' => now()], 'tutoria.solicitud_cancelada');
        });
    }

    /** El estudiante deja de compartir: el profesor pierde de inmediato el acceso a su información. */
    public function revoke(Request $http, Student $student): void
    {
        DB::transaction(function () use ($http, $student) {
            $term = AcademicTerm::current();
            $assignment = $term === null ? null : TutorAssignment::where('student_id', $student->id)->where('term_id', $term->id)->lockForUpdate()->first();

            if ($assignment === null) {
                throw new DomainException('No hay una tutoría vigente que dejar de compartir.');
            }

            TutorRequest::where('student_id', $student->id)->where('tutor_id', $assignment->tutor_id)
                ->where('estado', TutorRequest::ACCEPTED)->lockForUpdate()->get()
                ->each(fn (TutorRequest $r) => Audit::update($http, $r, ['estado' => TutorRequest::REVOKED], 'tutoria.solicitud_revocada'));

            Audit::log($http, $assignment, 'tutoria.vinculo_revocado', ['student_id' => $assignment->student_id, 'tutor_id' => $assignment->tutor_id, 'term_id' => $assignment->term_id]);
            $assignment->delete();
        });
    }

    /** Relee la solicitud con bloqueo: si otra petición la respondió mientras tanto, no se responde dos veces. */
    private function lockPending(TutorRequest $tutorRequest): TutorRequest
    {
        $locked = TutorRequest::whereKey($tutorRequest->id)->lockForUpdate()->firstOrFail();

        if (! $locked->isPending()) {
            throw new DomainException('Esta solicitud ya fue respondida o retirada.');
        }

        return $locked;
    }
}
