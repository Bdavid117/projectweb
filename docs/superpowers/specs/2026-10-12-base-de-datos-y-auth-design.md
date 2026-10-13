# Base de datos PostgreSQL y autenticación con roles — Diseño

Fecha: 2026-10-12
Sub-proyecto 1 de 3 (BD + auth → CRUD de pantallas existentes → pantallas nuevas del PDF).
Fuente del modelo: `mokup/Documento-Analisis-y-Diseno.pdf` §5 (modelo de datos) y §6 (rutas y roles).

## Objetivo

Pasar de un proyecto solo visual (datos mock en controladores, login simulado) a uno con:

1. Una base de datos PostgreSQL real con las tablas del documento de análisis (§5.3), modelos Eloquent y datos de demostración.
2. Login real con cuatro roles y permisos aplicados en el servidor.

Las pantallas siguen mostrando sus datos mock salvo los datos del usuario (`user`) y la navegación, que pasan a depender de la sesión. Conectar cada pantalla a la BD es el sub-proyecto 2.

## Fuera de alcance

- CRUD de estudiantes, solicitudes, tutorías, experiencias, etc. (sub-proyecto 2).
- Pantallas nuevas (`/mi-perfil`, `/admin/*`, …) (sub-proyecto 3).
- Recuperar contraseña (RF-04), cargas desde Excel, sincronización con API, exportaciones.
- Renombrar rutas actuales a las del PDF (`/dashboard`, `/solicitudes/crear`).

## Decisiones

| Tema | Decisión | Motivo |
|---|---|---|
| Roles | Columna `users.role` con enum PHP `App\Enums\Role` (`estudiante`, `profesor`, `tutor`, `coordinacion`) + middleware `role` y Gates | Es lo que define el PDF (§5.3); 4 roles fijos no justifican `spatie/laravel-permission` |
| Auth | Sesión nativa de Laravel (`Auth::attempt`, CSRF, bcrypt), sin Breeze/Fortify | El login ya está maquetado; Breeze lo reemplazaría |
| Nombres | Tablas en inglés y columnas en español, tal cual el PDF (`codigo`, `cohorte`, `papa`…) | El código coincide con el documento que el equipo sustenta |
| Migraciones | Una por tabla, en orden de dependencias | Revisables y reversibles por separado |
| Historicidad | `UNIQUE (student_id, term_id)` en `academic_records` y `tutor_assignments`; historias acumulan filas | PDF §5.5 |
| Borrado lógico | `deleted_at` (`SoftDeletes`) en `tutorships`, `requests`, `interventions` | PDF §5.5 |
| Datos sensibles | `health_profiles` en tabla propia | Una sola policy y auditoría completa (RNF-05) |

## 1. Base de datos

### Creación y configuración

- Base `trayectoria_estudiantil` (y `trayectoria_estudiantil_test` para tests), creadas con `psql -d postgres -c 'CREATE DATABASE …'` usando el socket local (el usuario `whoamy` es superusuario y no requiere contraseña por socket).
- `.env` y `.env.example`: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=trayectoria_estudiantil`, `DB_USERNAME=whoamy`, `DB_PASSWORD=` (se ajusta si la conexión TCP exige contraseña).
- `phpunit.xml`: `DB_CONNECTION=pgsql`, `DB_DATABASE=trayectoria_estudiantil_test`.
- Se quita la tabla/migración por defecto de `users` y se reemplaza por la del PDF; `password_reset_tokens`, `failed_jobs` y `personal_access_tokens` de Laravel se conservan.

### Tablas (las 25 de PDF §5.3, en orden de migración)

1. `users` (id, name, email único, password, role, active, remember_token, timestamps)
2. `academic_terms` (codigo único, fecha_inicio, fecha_fin)
3. `schools` (nombre, caracter, municipio)
4. `companies` (nombre, nit, sector, tamano, ciudad)
5. `request_types` (nombre, descripcion)
6. `experience_types` (nombre, categoria)
7. `committee_minutes` (numero_acta, fecha, instancia, url)
8. `students` (user_id nullable único → users, codigo único, documento único, cohorte, estado, fecha_ingreso)
9. `admission_profiles` (student_id único, school_id, puntaje_saber11, puntaje_admision, hobby_formal)
10. `health_profiles` (student_id único, resultado_oso, resultado_salud_mental, resultado_salud_fisica, observaciones)
11. `saber_pro_results` (student_id, fecha_presentacion, puntaje_global, resultados_json)
12. `academic_records` (student_id, term_id, papa, promedio_periodo, cupo_creditos, creditos_cursados, porcentaje_avance, numero_matricula, sincronizado_en; UNIQUE student_id+term_id)
13. `theses` (student_id, modalidad, estado, titulo, director_id, codirector_id → users, term_id, repositorio_url)
14. `internships` (student_id, company_id, jefe_nombre, jefe_cargo, asesor_id → users, fecha_inicio, fecha_fin, actividades, apreciacion_jefe, apreciacion_asesor)
15. `internship_follow_ups` (internship_id, fecha, observacion, autor_id → users)
16. `requests` (student_id, request_type_id, term_id, descripcion, estado, fecha_radicacion, minute_id nullable, decision, observaciones, deleted_at)
17. `request_attachments` (request_id, ruta, nombre_original, mime, tamano)
18. `request_status_history` (request_id, estado_anterior, estado_nuevo, user_id, fecha)
19. `experiences` (student_id, experience_type_id, nombre, descripcion, fecha_inicio, fecha_fin, alcance, institucion, convenio, docente_id, rol, validada, validada_por)
20. `tutor_assignments` (student_id, tutor_id → users, term_id; UNIQUE student_id+term_id)
21. `tutorships` (student_id, tutor_id, fecha, motivo, observaciones, compromisos, deleted_at)
22. `interventions` (student_id, origen, descripcion, responsable_id, fecha, resultado, estado, deleted_at)
23. `alerts` (student_id, tipo, detalle, fecha_deteccion, atendida)
24. `import_batches` (tipo, archivo, user_id, filas_ok, filas_error, log, created_at)
25. `audit_logs` (user_id, auditable_type, auditable_id, accion, valores_anteriores, valores_nuevos, ip, created_at)

Nota: el PDF menciona "veintiocho tablas" en §5.1, pero la tabla de §5.3 lista 25. Este spec implementa las 25 de §5.3; si el equipo tiene las 3 restantes definidas en los diagramas ER, se agregan como migraciones adicionales sin afectar el resto del diseño.

Tipos: `*_json` y `valores_*` como `jsonb`; fechas como `date`/`timestamp`; puntajes y PAPA como `decimal`. Claves foráneas con `constrained()` y `restrictOnDelete` por defecto (la historia no se borra en cascada), `cascadeOnDelete` solo para hijos puros (`request_attachments`, `internship_follow_ups`).

### Modelos

Un modelo por tabla en `app/Models/`, con `$fillable`, `casts` (fechas, `jsonb`, booleanos, enum `Role`) y las relaciones de PDF §5.2. `SoftDeletes` en `Tutorship`, `Request` (nombrado `CommitteeRequest` para no chocar con `Illuminate\Http\Request`, tabla `requests`) e `Intervention`.

### Seeders

- `DatabaseSeeder` llama a `CatalogSeeder` y `DemoSeeder`.
- `CatalogSeeder`: periodos (2019-1 … 2026-2), tipos de solicitud (los 5 del mock), tipos de experiencia (los del PDF §2.8), algunos colegios y empresas (incluye Softlogic S.A.S.).
- `DemoSeeder`: 4 usuarios (uno por rol, contraseña de desarrollo `password`), los 8 estudiantes del mock de `StudentController`, el historial académico de Juan Pérez Rojas, sus experiencias, tutorías, práctica y trabajo de grado, y solicitudes de ejemplo. Idempotente (`updateOrCreate`) para poder re-ejecutarlo.

### Tests de la BD

BD de pruebas separada con `RefreshDatabase`. Pruebas: las migraciones corren completas; `academic_records` rechaza duplicado student+term; `tutor_assignments` igual; los seeders corren sin error y crean un usuario por rol; `SoftDeletes` oculta tutorías/solicitudes eliminadas. Los tests de pantalla existentes siguen pasando sin depender de la BD.

## 2. Autenticación y roles

- `LoginController::store` valida `email` y `password`, hace `Auth::attempt` (solo usuarios `active`), regenera la sesión y redirige según rol: estudiante → `requests.create`, resto → `panel.index` (las rutas del PDF por rol llegan en el sub-proyecto 2). Fallo: vuelve a `/login` con un único error genérico "Credenciales inválidas" y conserva el correo (RF-01, RNF-10).
- `POST /logout` invalida la sesión y redirige a `/login`. El topbar muestra el botón de salir.
- Middleware `auth` en todas las rutas salvo `/login`; `guest` en `/login`.
- Middleware `EnsureRole` (alias `role`): `role:coordinacion,tutor` devuelve 403 si el rol no está permitido (RF-03, RNF-04). Matriz inicial aplicada a las rutas actuales:
  - `/panel`, `/indicadores`: coordinación (el PDF da `/indicadores` solo a coordinación).
  - `/estudiantes`, `/estudiantes/{id}`: profesor, tutor, coordinación.
  - `/solicitudes/nueva`: estudiante, coordinación.
- Los controladores toman `user` de `Auth::user()` (iniciales, nombre, rol legible) en lugar del array fijo, y eligen `Navigation::*` por rol.
- Contraseñas con bcrypt (cast `hashed` / `Hash::make`), CSRF activo en los formularios.

### Tests de auth

Login válido redirige por rol; inválido (correo inexistente y contraseña errónea) da el mismo mensaje; usuario inactivo no entra; invitado es redirigido a `/login`; cada rol recibe 403 en una ruta ajena y 200 en las propias; logout cierra la sesión; un POST a `/login` sin token CSRF devuelve 419 (el test reactiva el middleware CSRF, que Laravel desactiva en pruebas por defecto).

## Impacto en lo existente

- Los tests de pantalla actuales (`LoginTest`, `StudentTest`, …) deben autenticar con un usuario del rol correspondiente (helper en `tests/TestCase.php`). Cambio esperado y declarado.
- `CLAUDE.md` se actualiza: ya no es "solo visual"; documenta Postgres, roles y seeders.
- Sin cambios de diseño visual.

## Criterios de éxito

1. `php artisan migrate:fresh --seed` crea las 25 tablas de dominio en `trayectoria_estudiantil` sin errores.
2. Se puede iniciar sesión con cada uno de los 4 usuarios demo y ver el panel de su rol.
3. Un rol sin permiso recibe 403 al pedir por URL una ruta ajena.
4. `php artisan test` pasa completo.
