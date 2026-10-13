<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\CommitteeMinute;
use App\Models\CommitteeRequest;
use App\Models\RequestStatusHistory;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RequestWorkflow
{
    public const DECISIONS = ['aprobada', 'rechazada', 'aplazada'];

    /** @param  array<int, UploadedFile>  $files */
    public function file(Student $student, RequestType $type, AcademicTerm $term, string $descripcion, array $files, User $actor): CommitteeRequest
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($student, $type, $term, $descripcion, $files, $actor, &$stored) {
                $request = CommitteeRequest::create([
                    'student_id' => $student->id,
                    'request_type_id' => $type->id,
                    'term_id' => $term->id,
                    'descripcion' => $descripcion,
                    'estado' => 'radicada',
                    'fecha_radicacion' => now(),
                ]);

                foreach ($files as $file) {
                    $path = $file->store("soportes/{$request->id}");
                    if ($path === false) {
                        throw new DomainException('No se pudo guardar un soporte.');
                    }
                    $stored[] = $path;
                    $request->attachments()->create([
                        'ruta' => $path,
                        'nombre_original' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType() ?? 'application/octet-stream',
                        'tamano' => $file->getSize(),
                    ]);
                }

                $this->record($request, null, 'radicada', $actor);

                return $request;
            });
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                Storage::delete($path);
            }

            throw $e;
        }
    }

    public function startReview(CommitteeRequest $request, User $actor): void
    {
        DB::transaction(function () use ($request, $actor) {
            $locked = CommitteeRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->estado !== 'radicada') {
                throw new DomainException('La solicitud ya fue pasada a revisión.');
            }

            $locked->update(['estado' => 'en_revision']);
            $this->record($locked, 'radicada', 'en_revision', $actor);
        });

        $request->refresh();
    }

    public function decide(CommitteeRequest $request, string $decision, string $numeroActa, ?string $observaciones, User $actor): void
    {
        if (! in_array($decision, self::DECISIONS, true)) {
            throw new DomainException('La decisión no es válida.');
        }

        DB::transaction(function () use ($request, $decision, $numeroActa, $observaciones, $actor) {
            $locked = CommitteeRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->estado === 'con_decision') {
                throw new DomainException('La solicitud ya tiene una decisión registrada.');
            }
            if ($locked->estado !== 'en_revision') {
                throw new DomainException('Pase la solicitud a revisión antes de registrar la decisión.');
            }

            $minute = CommitteeMinute::firstOrCreate(
                ['numero_acta' => $numeroActa],
                ['fecha' => today(), 'instancia' => 'Comité asesor']
            );

            $locked->update([
                'estado' => 'con_decision',
                'decision' => $decision,
                'minute_id' => $minute->id,
                'observaciones' => $observaciones,
            ]);
            $this->record($locked, 'en_revision', 'con_decision', $actor);
        });

        $request->refresh();
    }

    public function repeatedCount(CommitteeRequest $request): int
    {
        return CommitteeRequest::where('student_id', $request->student_id)
            ->where('request_type_id', $request->request_type_id)
            ->where('term_id', $request->term_id)
            ->where('id', '!=', $request->id)
            ->count();
    }

    /** @return array<int, string> */
    public function existingTypeNames(Student $student, AcademicTerm $term): array
    {
        return CommitteeRequest::with('type')
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->get()
            ->pluck('type.nombre')
            ->unique()
            ->values()
            ->all();
    }

    private function record(CommitteeRequest $request, ?string $from, string $to, User $actor): void
    {
        RequestStatusHistory::create([
            'request_id' => $request->id,
            'estado_anterior' => $from,
            'estado_nuevo' => $to,
            'user_id' => $actor->id,
            'fecha' => now(),
        ]);
    }
}
