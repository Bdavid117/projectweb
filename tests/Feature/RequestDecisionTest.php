<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeMinute;
use App\Models\CommitteeRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestDecisionTest extends TestCase
{
    use RefreshDatabase;

    private CommitteeRequest $juan;

    private CommitteeRequest $laura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->juan = CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', '2020123'))->firstOrFail();
        $this->laura = CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', '2021045'))->firstOrFail();
    }

    private function decision(array $overrides = []): array
    {
        return $overrides + ['decision' => 'aprobada', 'numero_acta' => '12-2026', 'observaciones' => 'Procede por soporte médico.'];
    }

    public function test_coordination_moves_a_request_through_review_and_decision_with_a_full_trail(): void
    {
        $coordinator = $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->juan->id;

        $this->get($url)->assertSee('Pasar a revisión')->assertDontSee('Registrar decisión');

        $this->post($url.'/revision')->assertRedirect($url)->assertSessionHas('status');
        $this->assertSame('en_revision', $this->juan->fresh()->estado);

        $this->get($url)->assertSee('Registrar decisión')->assertDontSee('Pasar a revisión');

        $this->post($url.'/decision', $this->decision())->assertRedirect($url)->assertSessionHas('status');

        $request = $this->juan->fresh();
        $this->assertSame('con_decision', $request->estado);
        $this->assertSame('aprobada', $request->decision);
        $this->assertSame('12-2026', $request->minute->numero_acta);
        $this->assertSame(
            [['radicada', null], ['en_revision', 'radicada'], ['con_decision', 'en_revision']],
            $request->statusHistory()->orderBy('id')->get()->map(fn ($h) => [$h->estado_nuevo, $h->estado_anterior])->all()
        );
        $this->assertSame($coordinator->id, $request->statusHistory()->latest('id')->first()->user_id);

        $this->get($url)->assertSee('Aprobada')->assertSee('12-2026')->assertSee('Procede por soporte médico.')->assertDontSee('Registrar decisión');
    }

    public function test_deciding_without_reviewing_first_is_refused_and_changes_nothing(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->juan->id;

        $this->from($url)->post($url.'/decision', $this->decision())
            ->assertRedirect($url)
            ->assertSessionHasErrors(['estado' => 'Pase la solicitud a revisión antes de registrar la decisión.']);

        $this->assertSame('radicada', $this->juan->fresh()->estado);
        $this->assertSame(1, $this->juan->statusHistory()->count());
        $this->assertSame(0, CommitteeMinute::count());
    }

    public function test_a_second_decision_is_refused_and_keeps_the_first_one(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->laura->id;

        $this->post($url.'/decision', $this->decision())->assertSessionHasNoErrors();
        $this->from($url)->post($url.'/decision', $this->decision(['decision' => 'rechazada', 'numero_acta' => '13-2026']))
            ->assertSessionHasErrors(['estado' => 'La solicitud ya tiene una decisión registrada.']);

        $request = $this->laura->fresh();
        $this->assertSame('aprobada', $request->decision);
        $this->assertSame('12-2026', $request->minute->numero_acta);
        $this->assertSame(2, $request->statusHistory()->count());
        $this->assertSame(1, CommitteeMinute::count());
    }

    public function test_reviewing_twice_is_refused(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->laura->id;

        $this->from($url)->post($url.'/revision')->assertSessionHasErrors(['estado' => 'La solicitud ya fue pasada a revisión.']);
        $this->assertSame(1, $this->laura->statusHistory()->count());
    }

    public function test_invalid_decision_data_is_rejected_with_messages_and_old_input(): void
    {
        $this->signInSeeded(Role::Coordinacion);
        $url = '/solicitudes/'.$this->laura->id;

        $this->from($url)->post($url.'/decision', ['decision' => 'quizás', 'observaciones' => 'Texto previo'])
            ->assertRedirect($url)
            ->assertSessionHasErrors(['decision', 'numero_acta']);

        $this->assertSame('en_revision', $this->laura->fresh()->estado);
        $this->get($url)->assertSee('Texto previo')->assertSee('Indique el número de acta.');
    }

    public function test_only_coordination_can_review_or_decide(): void
    {
        $url = '/solicitudes/'.$this->laura->id;

        foreach ([Role::Estudiante, Role::Profesor, Role::Tutor] as $role) {
            $this->signInSeeded($role);
            $this->post($url.'/revision')->assertForbidden();
            $this->post($url.'/decision', $this->decision())->assertForbidden();
        }

        $this->actingAs(User::where('email', 'est2021045@unal.edu.co')->firstOrFail());
        $this->post($url.'/decision', $this->decision())->assertForbidden();

        $this->assertSame('en_revision', $this->laura->fresh()->estado);
        $this->assertSame(1, $this->laura->statusHistory()->count());
    }

    public function test_guests_are_sent_to_login_and_unknown_requests_return_404(): void
    {
        $this->post('/solicitudes/'.$this->laura->id.'/decision', $this->decision())->assertRedirect('/login');

        $this->signInSeeded(Role::Coordinacion);
        $this->post('/solicitudes/999999/revision')->assertNotFound();
        $this->post('/solicitudes/abc/decision', $this->decision())->assertNotFound();
    }
}
