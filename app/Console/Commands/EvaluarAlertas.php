<?php

namespace App\Console\Commands;

use App\Support\AlertRules;
use Illuminate\Console\Command;

class EvaluarAlertas extends Command
{
    protected $signature = 'alertas:evaluar {--simular : Muestra las alertas que crearía, sin guardar nada}';

    protected $description = 'Evalúa las reglas de alertas tempranas (RF-37) y crea las que falten';

    public function handle(AlertRules $rules): int
    {
        $dry = (bool) $this->option('simular');
        $rows = $rules->run(dryRun: $dry);

        if ($rows === []) {
            $this->info('Ninguna alerta nueva.');

            return self::SUCCESS;
        }

        $this->table(['Código', 'Estudiante', 'Regla', 'Detalle'], array_map(fn ($r) => [$r['code'], $r['name'], $r['tipo'], $r['detalle']], $rows));
        $count = count($rows);
        $this->info(($dry ? 'Se crearían ' : 'Se crearon ').$count.' '.($count === 1 ? 'alerta' : 'alertas').'.');

        return self::SUCCESS;
    }
}
