<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\CommitteeRequest;
use App\Models\RequestAttachment;
use App\Models\Student;
use App\Models\TutorRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

/**
 * El profesor solo ve la información de los estudiantes que aceptaron su solicitud de tutoría. Cada pantalla que
 * muestra datos de un estudiante se comprueba con un profesor vinculado (Carlos Pineda), uno sin vínculos (María
 * Londoño) y Coordinación, que no pasa por consentimiento.
 */
class StudentScopeTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private User $maria;

    private User $carlos;

    private User $coordinator;

    private Student $juan;

    private Student $ruiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->maria = User::where('email', 'profesor@unal.edu.co')->firstOrFail();
        $this->carlos = User::where('email', 'profesor2@unal.edu.co')->firstOrFail();
        $this->coordinator = User::where('email', 'coordinacion@unal.edu.co')->firstOrFail();
        $this->juan = Student::where('codigo', '2020123')->firstOrFail();   // tutorado de Carlos
        $this->ruiz = Student::where('codigo', '2019087')->firstOrFail();   // sin tutor
    }

    public function test_the_roster_lists_only_tutored_students_and_the_search_cannot_widen_it(): void
    {
        $this->actingAs($this->carlos);
        $this->get('/estudiantes')->assertOk()->assertSee('Mostrando 1–5 de 5 estudiantes')->assertSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Ruiz Salazar, Andrés');
        $this->get('/estudiantes?q=Ruiz')->assertDontSee('Ruiz Salazar, Andrés');
        $this->get('/estudiantes?q=2019087')->assertDontSee('Ruiz Salazar, Andrés');
        $this->get('/estudiantes?cohorte=2019-2')->assertDontSee('Ruiz Salazar, Andrés');

        $this->actingAs($this->maria)->get('/estudiantes')->assertOk()->assertDontSee('Pérez Rojas, Juan Sebastián')->assertSee('Aún no tiene tutorados');

        $this->actingAs($this->coordinator)->get('/estudiantes')->assertSee('Mostrando 1–8 de 8 estudiantes');
    }

    public function test_the_cohort_filter_only_offers_cohorts_of_visible_students(): void
    {
        $this->actingAs($this->carlos)->get('/estudiantes')->assertViewHas('cohortOptions', fn ($options) => $options->keys()->all() === ['2020-1', '2021-1', '2021-2', '2022-1', '2022-2']);
        $this->actingAs($this->maria)->get('/estudiantes')->assertViewHas('cohortOptions', fn ($options) => $options->isEmpty());
        $this->actingAs($this->coordinator)->get('/estudiantes')->assertViewHas('cohortOptions', fn ($options) => $options->count() === 8);
    }

    public function test_the_tutor_filter_and_the_active_counter_do_not_leak_other_students(): void
    {
        $this->actingAs($this->carlos)->get('/estudiantes')->assertDontSee('Tutor: todos')->assertSee('4 estudiantes activos entre sus tutorados');
        $this->actingAs($this->coordinator)->get('/estudiantes')->assertSee('Tutor: todos')->assertSee('6 estudiantes activos en el programa');
    }

    public function test_a_professor_without_tutored_students_is_told_how_to_get_some_unless_they_are_filtering(): void
    {
        $this->actingAs($this->maria)->get('/estudiantes')->assertOk()
            ->assertSee('Aún no tiene tutorados')->assertSee('0 estudiantes activos entre sus tutorados')
            ->assertSee('href="'.route('tutor-requests.index').'"', false);

        // Con un filtro puesto el mensaje es el de siempre: no hay coincidencias.
        $this->actingAs($this->maria)->get('/estudiantes?q=zzz')->assertSee('No hay estudiantes que coincidan con los filtros.')->assertDontSee('Aún no tiene tutorados');
        $this->actingAs($this->coordinator)->get('/estudiantes?q=zzz')->assertSee('No hay estudiantes que coincidan con los filtros.');
    }

    public function test_the_profile_opens_only_for_the_linked_professor_and_coordination(): void
    {
        $this->actingAs($this->carlos)->get(route('students.show', $this->juan))->assertOk();
        $this->actingAs($this->carlos)->get(route('students.show', $this->ruiz))->assertForbidden();
        $this->actingAs($this->maria)->get(route('students.show', $this->juan))->assertForbidden();
        $this->actingAs($this->coordinator)->get(route('students.show', $this->ruiz))->assertOk();
    }

    public function test_requests_of_an_untutored_student_are_invisible_to_the_professor_and_so_are_their_files(): void
    {
        Storage::fake('local');
        $request = $this->makeRequest($this->ruiz);
        Storage::put('soportes/'.$request->id.'/carta.pdf', 'contenido');
        $attachment = RequestAttachment::create(['request_id' => $request->id, 'ruta' => 'soportes/'.$request->id.'/carta.pdf', 'nombre_original' => 'carta.pdf', 'mime' => 'application/pdf', 'tamano' => 9]);

        $this->actingAs($this->carlos);
        $this->get('/solicitudes')->assertOk()->assertDontSee('Ruiz Salazar, Andrés');
        $this->get('/solicitudes?q=Ruiz')->assertDontSee('Ruiz Salazar, Andrés');
        $this->get(route('requests.show', $request))->assertForbidden();
        $this->get(route('requests.attachments.show', [$request, $attachment]))->assertForbidden();

        $this->actingAs($this->coordinator)->get(route('requests.attachments.show', [$request, $attachment]))->assertOk();
    }

    public function test_requests_of_a_tutored_student_open_for_the_linked_professor_only(): void
    {
        $request = CommitteeRequest::where('student_id', $this->juan->id)->firstOrFail();

        $this->actingAs($this->carlos)->get(route('requests.show', $request))->assertOk();
        $this->actingAs($this->maria)->get(route('requests.show', $request))->assertForbidden();
    }

    public function test_experiences_of_an_untutored_student_stay_hidden_from_the_professor(): void
    {
        $this->actingAs($this->maria)->get('/experiencias')->assertOk()->assertSee('0 registradas');
        $this->actingAs($this->carlos)->get('/experiencias')->assertOk()->assertSee('Semillero de investigación SIGMA');
    }

    public function test_the_alerts_table_and_recent_activity_only_list_tutored_students_for_the_professor(): void
    {
        $this->actingAs($this->carlos)->get('/panel')->assertOk()
            ->assertSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Ruiz Salazar, Andrés');
        $this->actingAs($this->maria)->get('/panel')->assertOk()
            ->assertDontSee('Pérez Rojas, Juan Sebastián')->assertDontSee('Ruiz Salazar, Andrés')->assertDontSee('Tutoría registrada a');
    }

    public function test_the_link_lapses_when_a_new_term_with_academic_records_becomes_the_current_one(): void
    {
        $this->actingAs($this->carlos)->get(route('students.show', $this->juan))->assertOk();

        $next = AcademicTerm::create(['codigo' => '2027-1', 'fecha_inicio' => '2027-02-01', 'fecha_fin' => '2027-06-15']);
        AcademicRecord::create(['student_id' => $this->juan->id, 'term_id' => $next->id, 'papa' => 4.0, 'promedio_periodo' => 4.0, 'cupo_creditos' => 21, 'creditos_cursados' => 120, 'porcentaje_avance' => 75, 'numero_matricula' => 8]);
        $this->assertSame('2027-1', AcademicTerm::current()->codigo);

        // El vínculo es por periodo: hay que volver a solicitarlo y que el estudiante lo acepte.
        $this->actingAs($this->carlos)->get(route('students.show', $this->juan))->assertForbidden();
        $this->actingAs($this->carlos)->get('/mis-asesorias')->assertSee('0 estudiantes asignados');

        $request = TutorRequest::create(['student_id' => $this->juan->id, 'tutor_id' => $this->carlos->id, 'estado' => TutorRequest::PENDING]);
        $this->actingAs($this->juan->user)->post(route('my-tutor.accept', $request))->assertSessionHasNoErrors();

        $this->actingAs($this->carlos)->get(route('students.show', $this->juan))->assertOk();
    }
}
