<?php

namespace App\Support;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Alert;
use App\Models\CommitteeRequest;
use App\Models\TutorAssignment;
use App\Models\Tutorship;

/**
 * Alertas tempranas (RF-37). Evalúa las reglas de `config/program.php` sobre el periodo vigente y crea las alertas que falten.
 * Es idempotente: no duplica una alerta abierta del mismo tipo para el mismo estudiante, ni vuelve a avisar en el mismo periodo
 * de algo que ya se atendió. Solo mira a estudiantes activos o en riesgo.
 */
class AlertRules
{
    /** @return list<array{student_id: int, name: string, code: string, tipo: string, detalle: string}> las alertas creadas (o que se crearían con $dryRun). */
    public function run(?AcademicTerm $term = null, bool $dryRun = false): array
    {
        $term ??= AcademicTerm::current();
        if (! $term) {
            return [];
        }

        $rules = collect(config('program.alert_rules'))->filter(fn ($rule) => $rule['activa'] ?? true);
        $records = AcademicRecord::where('term_id', $term->id)
            ->whereHas('student', fn ($q) => $q->whereIn('estado', ['activo', 'en_riesgo']))
            ->with('student.user')->get();
        $previous = AcademicRecord::previousFor($records->pluck('student_id')->all(), $term);

        $found = collect();
        foreach ($records as $record) {
            $before = $previous->get($record->student_id);
            $papa = (float) $record->papa;

            if ($rules->has('papa_bajo') && $papa < (float) config('program.papa_alert')) {
                $found->push([$record->student, 'papa_bajo', 'PAPA de '.Labels::decimal($papa).', por debajo del mínimo de '.Labels::decimal(config('program.papa_alert')).'.']);
            }
            if ($rules->has('caida_papa') && $before && $papa <= (float) $before->papa - (float) $rules['caida_papa']['decimas'] + 1e-9) {
                $found->push([$record->student, 'caida_papa', 'El PAPA cayó de '.Labels::decimal($before->papa).' a '.Labels::decimal($papa).' frente al periodo anterior ('.$before->term->codigo.').']);
            }
            if ($rules->has('matricula_sin_avance') && $before && (int) $record->creditos_cursados <= (int) $before->creditos_cursados) {
                $found->push([$record->student, 'matricula_sin_avance', 'Sin créditos nuevos frente al periodo anterior ('.$before->term->codigo.'): '.$record->creditos_cursados.' cursados.']);
            }
        }

        if ($rules->has('solicitudes_recurrentes')) {
            $minimum = (int) $rules['solicitudes_recurrentes']['minimo'];
            $repeats = CommitteeRequest::where('term_id', $term->id)->with('type')
                ->selectRaw('student_id, request_type_id, count(*) as total')->groupBy('student_id', 'request_type_id')
                ->having(\DB::raw('count(*)'), '>=', $minimum)->get();
            foreach ($repeats as $row) {
                $student = $records->firstWhere('student_id', $row->student_id)?->student;
                $student && $found->push([$student, 'solicitudes_recurrentes', $row->total.' solicitudes de «'.$row->type->nombre.'» en el periodo '.$term->codigo.'.']);
            }
        }

        if ($rules->has('sin_tutoria')) {
            $days = (int) $rules['sin_tutoria']['dias'];
            $last = Tutorship::selectRaw('student_id, max(fecha) as ultima')->groupBy('student_id')->pluck('ultima', 'student_id');
            $tutored = TutorAssignment::where('term_id', $term->id)->pluck('student_id')->flip();
            foreach ($records as $record) {
                $date = $last->get($record->student_id);
                if ($tutored->has($record->student_id) && ($date === null || now()->diffInDays($date, false) < -$days)) {
                    $found->push([$record->student, 'sin_tutoria', $date === null ? 'Aún sin ninguna tutoría registrada.' : 'Sin tutoría registrada en los últimos '.$days.' días.']);
                }
            }
        }

        return $this->store($found, $term, $dryRun);
    }

    private function store($found, AcademicTerm $term, bool $dryRun): array
    {
        $existing = Alert::whereIn('student_id', $found->map(fn ($f) => $f[0]->id)->unique())->get()->groupBy(fn ($a) => $a->student_id.'|'.$a->tipo);
        $created = [];

        foreach ($found as [$student, $tipo, $detail]) {
            // Abierta, o atendida dentro de este mismo periodo: no se repite.
            $blocks = $existing->get($student->id.'|'.$tipo, collect())->contains(fn ($a) => ! $a->atendida || $a->fecha_deteccion >= $term->fecha_inicio);
            if ($blocks) {
                continue;
            }
            $dryRun || Alert::create(['student_id' => $student->id, 'tipo' => $tipo, 'detalle' => $detail, 'fecha_deteccion' => today(), 'atendida' => false]);
            $created[] = ['student_id' => $student->id, 'name' => $student->user?->name ?? $student->codigo, 'code' => $student->codigo, 'tipo' => $tipo, 'detalle' => $detail];
        }

        return $created;
    }
}
