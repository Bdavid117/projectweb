<?php

return [
    // PAPA mínimo del programa: por debajo se resalta en rojo y, desde que corren las reglas de abajo, dispara una alerta.
    'papa_alert' => 3.0,
    // Días sin una tutoría para considerar que un estudiante acompañado está «sin tutoría reciente» (mapa del panel y Mis asesorías).
    'tutorship_stale_days' => 90,
    // Semestres del plan de estudios. SUPUESTO del desarrollo (el análisis solo fija 160 créditos): con él se calcula el avance esperado.
    'plan_semesters' => 10,
    // Puntos de avance por debajo de lo esperado a partir de los cuales alguien se considera «rezagado» (margen de lo razonable: el plan no es lineal).
    'lag_margin' => 15,
    'page_size' => 8,
    'total_credits' => 160,
    'tutorship_edit_hours' => 24,
    'attachment_max_kb' => 5120,
    'attachment_max_files' => 5,
    /*
     * Reglas de alertas tempranas (RF-37), evaluadas cada día por `php artisan alertas:evaluar`. Son parámetros del programa: los valores
     * por defecto son una PROPUESTA del desarrollo (el documento de análisis define los tipos de regla, no los umbrales); cambie `activa`
     * o el umbral de cada una según la política de Coordinación. Cada regla avisa una vez por periodo y estudiante mientras no se atienda.
     */
    'alert_rules' => [
        // Más allá de RF-37, que habla solo de «caída»: avisa cuando el PAPA está por debajo de `papa_alert`.
        'papa_bajo' => ['activa' => true],
        // El PAPA bajó al menos `decimas` frente al periodo anterior con registro.
        'caida_papa' => ['activa' => true, 'decimas' => 0.3],
        // Los créditos cursados no aumentaron frente al periodo anterior con registro.
        'matricula_sin_avance' => ['activa' => true],
        // `minimo` solicitudes del mismo tipo en el periodo (las eliminadas no cuentan).
        'solicitudes_recurrentes' => ['activa' => true, 'minimo' => 3],
        // Estudiante con tutor asignado este periodo y sin tutoría registrada en `dias` días.
        'sin_tutoria' => ['activa' => true, 'dias' => 180],
    ],
    'tutorship_reasons' => [
        'Seguimiento a carga académica',
        'Orientación sobre modalidad de grado',
        'Revisión de avance',
        'Seguimiento de solicitud',
        'Alerta temprana',
        'Otro',
    ],
];
