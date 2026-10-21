# CRUD de solicitudes y tutorías y conexión de las pantallas a la base de datos — Diseño

Fecha: 2026-10-12
Sub-proyecto 2 de 3. Base: `docs/superpowers/specs/2026-10-12-base-de-datos-y-auth-design.md` (BD, modelos, roles y login ya implementados).
Diseño visual: `mokup/trayectoria-estudiantil.pen`, organizado en carriles por rol (C1–C5 Coordinación, T1–T3 Tutor, E1 Estudiante). Los marcos 16–19 (Solicitudes, Detalle, Registrar tutoría, Mis asesorías) se dibujaron en esta etapa; cualquier ajuste visual posterior se aplica al construir la vista.

## Objetivo

Que las pantallas dejen de usar datos mock: cada una lee de PostgreSQL y las acciones de escritura de solicitudes y tutorías funcionan con validación, permisos y trazabilidad.

## Alcance

**Dentro**
- Listado y ficha de estudiantes con datos reales, búsqueda, filtros y paginación (RF-07, RF-08).
- Solicitudes: radicar con soportes, listar con filtros, ver detalle y traza, decidir (RF-25 a RF-29).
- Tutorías: registrar, editar y eliminar dentro de 24 h, historial, "Mis asesorías" (RF-34, RF-35).
- Panel e Indicadores calculados desde la BD (RF-38).
- Políticas de autorización por rol y por propiedad (el estudiante solo ve lo suyo).

**Fuera** (sub-proyecto 3 o posteriores)
- `/mi-perfil`, `/mi-historia`, `/mi-trabajo-de-grado`, `/mi-practica`, `/experiencias`, bloque `/admin/*`.
- Alertas automáticas (RF-37), cargas desde Excel, exportaciones (RF-39), perfil de salud visible (RNF-05; solo se mantiene el aviso de acceso restringido).
- Vistas móviles nuevas y estados vacío/error dibujados en Pencil.

## Decisiones

| Tema | Decisión | Motivo |
|---|---|---|
| Contrato de las vistas | Los controladores arman los **mismos arrays** que hoy reciben las vistas Blade (`students`, `attentionRows`, `papaChart`…). Las vistas existentes casi no cambian | Menor riesgo visual; el mockup sigue siendo la fuente de verdad |
| Rutas | Se conservan `GET /solicitudes/nueva` y `GET /estudiantes/{student}` (id numérico, 404 si no existe). Se añaden las del PDF §6.1 para tutorías y asesorías. `POST /solicitudes/nueva` pasa a `POST /solicitudes` | Los enlaces y tests actuales usan `nueva`; el PDF usa `crear`, pero el costo de renombrar no aporta valor |
| Autorización | Policies (`CommitteeRequestPolicy`, `TutorshipPolicy`, `StudentPolicy`) además del middleware `role:`. Cierra el hueco "Gates" del spec 1 | Reglas por propiedad (estudiante) y por tiempo (24 h) no caben en el middleware |
| Servicios | `App\Services\RequestWorkflow` (radicar, decidir, historial de estados) y `App\Support\ProgramMetrics` (consultas de Panel e Indicadores). Controladores delgados | Aísla la lógica de negocio y permite probarla sin HTTP |
| Soportes | Disco privado `local`; PDF/JPG/PNG, máx. 5 MB, hasta 5 archivos; descarga por ruta autorizada | RF-25; nunca URL pública |
| Panel del estudiante | `/panel` redirige al Estudiante a `/solicitudes` hasta diseñar su panel | Evita mostrarle datos de otros estudiantes (hallazgo de la revisión del spec 1) |
| Menú por rol | `Navigation::forRole` oculta lo que daría 403: **Indicadores** solo a Coordinación; **Solicitudes** apunta a `/solicitudes` (lectura para Tutor y Profesor, que ya está permitido); el bloque **Administración** solo a Coordinación. El Estudiante conserva su menú del mockup | Resuelve el pendiente I5 del spec 1 |
| Datos del mock | Las cifras del mockup (312 estudiantes…) pasan a ser las reales de la BD sembrada (8 estudiantes); las pruebas usan los datos del seeder | Los números dejan de ser ilustrativos |

## 1. Estudiantes

- `GET /estudiantes` (profesor, tutor, coordinación): `StudentController@index` consulta `students` + usuario + último `academic_record`; búsqueda por nombre, código o documento (`ILIKE`), filtros combinables por cohorte, estado y tutor (vía `tutor_assignments` del periodo vigente), 8 por página, `withQueryString()`. El PAPA bajo 3.0 se marca en rojo (umbral en `config/program.php`, clave `papa_alert`).
- `GET /estudiantes/{student}`: ficha con serie de PAPA por periodo (`academic_records`), avance, línea de tiempo de `experiences`, procesos (`theses`, `internships`), tutorías y aviso de información restringida. 404 si no existe.
- Formato de estado visible: `activo`→Activo/success, `en_riesgo`→En riesgo/danger, `intercambio`→Intercambio/info.

## 2. Solicitudes

Estados: `radicada` → `en_revision` → `con_decision`; decisión: `aprobada`, `rechazada`, `aplazada`.

| Ruta | Quién | Acción |
|---|---|---|
| `GET /solicitudes` | todos | Estudiante: solo las propias. Resto: todas. Filtros tipo/estado/periodo + búsqueda por estudiante; paginación 8 |
| `GET /solicitudes/nueva` | estudiante, coordinación | Formulario (E1). Coordinación elige estudiante por código |
| `POST /solicitudes` | estudiante, coordinación | Valida; crea `CommitteeRequest` (`radicada`, fecha/hora actual, periodo vigente), guarda soportes, crea la primera fila de `request_status_history` en una transacción |
| `GET /solicitudes/{solicitud}` | dueño, profesor, tutor, coordinación | Detalle, soportes y traza (C4) |
| `POST /solicitudes/{solicitud}/decision` | coordinación | Registra decisión, `numero_acta` (reutiliza o crea `CommitteeMinute`), observaciones; estado → `con_decision`; fila en historial con el usuario |
| `POST /solicitudes/{solicitud}/revision` | coordinación | Pasa de `radicada` a `en_revision` (botón en C4) |
| `GET /solicitudes/{solicitud}/soportes/{soporte}` | quien puede ver la solicitud | Descarga desde disco privado |

Reglas: una solicitud con decisión no admite otra decisión (422/redirect con error); el estudiante nunca modifica estados (RF-26); al radicar, si ya existe otra del mismo tipo y periodo para ese estudiante se muestra el aviso en el detalle de Coordinación (RF-29) y una advertencia no bloqueante en el formulario. Validación en español, errores junto al campo y datos del formulario conservados (RNF-10).

## 3. Tutorías

| Ruta | Quién | Acción |
|---|---|---|
| `GET /mis-asesorias` | tutor | Estudiantes asignados en el periodo vigente con última tutoría, PAPA y alertas abiertas (T1) |
| `GET /estudiantes/{student}/tutorias/crear` | tutor, coordinación | Formulario (T3) con historial reciente |
| `POST /estudiantes/{student}/tutorias` | tutor, coordinación | Valida (fecha ≤ hoy, motivo y observaciones obligatorios), crea la tutoría con `tutor_id` = usuario autenticado |
| `GET/PUT /tutorias/{tutoria}` (`editar`) | autor, dentro de 24 h | Edita |
| `DELETE /tutorias/{tutoria}` | autor, dentro de 24 h | Borrado lógico |

Pasadas 24 h desde `created_at` la tutoría es inmutable: `TutorshipPolicy::update/delete` devuelve 403. Coordinación y tutor ven el historial completo del estudiante en la ficha (RF-35).

## 4. Panel e Indicadores

`ProgramMetrics` (única fuente de cálculo, con la fecha de corte visible):
- Panel: estudiantes activos, PAPA promedio y su variación frente al periodo anterior, avance medio, alertas abiertas; tabla de alertas (`alerts` no atendidas) con estudiante, detalle, fecha y estado; avance por cohorte; actividad reciente (últimas tutorías, solicitudes y experiencias validadas); accesos rápidos estáticos.
- Indicadores: tarjetas clave (activos, prácticas y trabajos de grado en curso, solicitudes del periodo y sin decisión), histograma de PAPA por rangos, participación en experiencias por categoría, solicitudes por tipo con radicadas/en revisión/decididas y tiempo medio de decisión.
- Los cálculos usan `academic_records` del periodo vigente (el más reciente de `academic_terms` con registros), no una lectura puntual.

## 5. Componentes y menú

- `x-form-field` muestra el error de validación del campo (`$errors`) y acepta `required`, `old()` y opciones para `select`; se añade un campo de archivos reutilizable.
- `Navigation::forRole` omite enlaces a rutas no permitidas para ese rol y muestra Administración solo a Coordinación; las rutas nuevas se enlazan en los ítems que hoy apuntan a `#`.

## Pruebas

- Pruebas de servicio para `RequestWorkflow` y `ProgramMetrics` con `RefreshDatabase` y datos del seeder.
- Pruebas de feature por ruta: éxito, validación, 403/404 por rol y propiedad, ventana de 24 h, persistencia de soportes (con `Storage::fake`), historial de estados.
- Los tests de pantalla existentes pasan a usar usuarios persistidos y los datos del seeder en lugar de los textos mock.

## Criterios de éxito

1. Un estudiante radica una solicitud con soporte y la ve en su lista; Coordinación la ve, la pasa a revisión y registra la decisión con acta; el historial muestra las tres transiciones.
2. Un estudiante recibe 403 al abrir la solicitud de otro y no ve datos de otros en `/panel` ni en `/solicitudes`.
3. Un tutor registra una tutoría, la edita antes de 24 h y recibe 403 pasado ese plazo.
4. `/estudiantes`, la ficha, el Panel y los Indicadores muestran datos de la BD sembrada; `/estudiantes/999999` da 404.
5. `php artisan test` pasa completo y `migrate:fresh --seed` sigue funcionando.
