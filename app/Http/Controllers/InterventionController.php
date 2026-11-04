<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\CloseInterventionRequest;
use App\Http\Requests\StoreInterventionRequest;
use App\Models\Alert;
use App\Models\Intervention;
use App\Models\Student;
use App\Support\Audit;
use App\Support\Labels;
use App\Support\Navigation;
use App\Support\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public function create(Request $request, Student $student): View
    {
        $this->authorize('create', [Intervention::class, $student]);

        $user = $request->user();

        return view('interventions.form', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, $user->role === Role::Profesor ? 'mis-asesorias' : 'estudiantes'),
            'student' => StudentProfile::header($student),
            'origenes' => Intervention::ORIGENES,
            'prefill' => $this->resolvePrefill($student, (string) $request->query('origen'), $request->query('origen_id')),
        ]);
    }

    public function store(StoreInterventionRequest $request, Student $student): RedirectResponse
    {
        $intervention = $student->interventions()->create($request->validated() + [
            'responsable_id' => $request->user()->id,
            'estado' => Intervention::ESTADO_ABIERTA,
        ]);

        Audit::log($request, $intervention, 'intervencion_registrada', [], $intervention->only(['origen', 'origen_id', 'descripcion', 'fecha', 'estado']));

        // Si nació de una alerta, esa alerta queda atendida: registrar la intervención cierra el ciclo (RF-37).
        if ($intervention->origen === 'alerta' && $intervention->origen_id) {
            $alerta = Alert::where('id', $intervention->origen_id)->where('student_id', $student->id)->first();
            if ($alerta) {
                Audit::update($request, $alerta, ['atendida' => true], 'alerta_atendida');
            }
        }

        return redirect()->route('students.show', $student)->with('status', 'Intervención registrada correctamente.');
    }

    public function close(Request $request, Intervention $intervencion): View|RedirectResponse
    {
        $this->authorize('update', $intervencion);

        if (! $intervencion->isAbierta()) {
            return redirect()->route('students.show', $intervencion->student_id)->with('status', 'Esta intervención ya estaba cerrada.');
        }

        $user = $request->user();
        $intervencion->loadMissing(['student.user', 'responsable']);

        return view('interventions.close', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, $user->role === Role::Profesor ? 'mis-asesorias' : 'estudiantes'),
            'student' => StudentProfile::header($intervencion->student),
            'intervention' => [
                'id' => $intervencion->id,
                'origen' => $intervencion->origenLabel(),
                'date' => Labels::date($intervencion->fecha),
                'description' => $intervencion->descripcion,
                'responsable' => $intervencion->responsable->name,
            ],
        ]);
    }

    public function storeClose(CloseInterventionRequest $request, Intervention $intervencion): RedirectResponse
    {
        if (! $intervencion->isAbierta()) {
            return redirect()->route('students.show', $intervencion->student_id)->with('status', 'Esta intervención ya estaba cerrada.');
        }

        Audit::update($request, $intervencion, [
            'resultado' => $request->validated()['resultado'],
            'estado' => Intervention::ESTADO_CERRADA,
            'cerrada_en' => now(),
        ], 'intervencion_cerrada');

        return redirect()->route('students.show', $intervencion->student_id)->with('status', 'Intervención cerrada correctamente.');
    }

    /**
     * Prefijado al abrir el formulario desde una alerta, solicitud o tutoría del estudiante.
     *
     * @return array{origen: string, origen_id: ?int, hint: ?string}
     */
    private function resolvePrefill(Student $student, string $origen, mixed $origenId): array
    {
        $origen = array_key_exists($origen, Intervention::ORIGENES) ? $origen : '';
        $class = Intervention::modelForOrigen($origen);
        $id = ctype_digit((string) $origenId) ? (int) $origenId : null;
        $record = $class && $id ? $class::where('id', $id)->where('student_id', $student->id)->first() : null;

        return [
            'origen' => $record ? $origen : '',
            'origen_id' => $record?->id,
            'hint' => $record ? $this->hintFor($origen, $record) : null,
        ];
    }

    private function hintFor(string $origen, object $record): string
    {
        return match ($origen) {
            'alerta' => 'Alerta: '.($record->detalle ?? $record->tipo),
            'solicitud' => 'Solicitud: '.($record->type->nombre ?? 'sin tipo'),
            'tutoria' => 'Tutoría del '.$record->fecha->format('d/m/Y').($record->motivo ? ' · '.$record->motivo : ''),
            default => '',
        };
    }
}
