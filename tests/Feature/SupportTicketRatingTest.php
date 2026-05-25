<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SupportTicket;
use App\Models\SupportTicketWorkLog;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketRatingTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        return User::factory()->create([
            'first_name' => 'Kupac',
            'last_name' => 'Testić',
        ]);
    }

    private function makeAgent(string $first = 'Agent', string $last = 'Smith'): User
    {
        $user = User::factory()->create([
            'first_name' => $first,
            'last_name' => $last,
        ]);

        Zaposlen::create([
            'user_id' => $user->id,
            'role' => 'agent',
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
        ]);

        return $user;
    }

    private function makeTicket(User $customer, string $status = 'open', ?string $outcome = null): SupportTicket
    {
        $category = Category::firstOrCreate(['name' => 'Test kategorija']);

        return SupportTicket::create([
            'user_id' => $customer->id,
            'category_id' => $category->id,
            'description' => 'Test problem opis',
            'number' => 'ST-TEST-'.uniqid(),
            'status' => $status,
            'priority' => 'medium',
            'outcome' => $outcome,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);
    }

    private function addWorkLog(SupportTicket $ticket, User $agent, string $action, ?string $endedAt = 'now'): SupportTicketWorkLog
    {
        return SupportTicketWorkLog::create([
            'support_ticket_id' => $ticket->id,
            'employee_id' => $agent->id,
            'started_at' => now()->subMinutes(5),
            'ended_at' => $endedAt === 'now' ? now() : $endedAt,
            'action' => $action,
        ]);
    }

    public function test_customer_can_rate_a_closed_ticket(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'resolution_speed' => 5,
            'communication_quality' => null,
            'degree_of_resolution' => null,
        ]);
        $this->assertDatabaseHas('support_ticket_rating_agent', [
            'employee_id' => $agent->id,
            'rating' => 4,
        ]);
    }

    public function test_rating_is_rejected_when_ticket_is_not_closed(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();

        $ticket = $this->makeTicket($customer, status: 'in_progress');
        $this->addWorkLog($ticket, $agent, 'taken_over', endedAt: null);

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
            ])
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseCount('support_ticket_rating', 0);
    }

    public function test_non_owner_cannot_rate_someone_elses_ticket(): void
    {
        $owner = $this->makeCustomer();
        $other = User::factory()->create();
        $agent = $this->makeAgent();

        $ticket = $this->makeTicket($owner, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($other)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('support_ticket_rating', 0);
    }

    public function test_customer_cannot_rate_twice(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $payload = [
            'resolution_speed' => 5,
            'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
        ];

        $this->actingAs($customer)->post("/support-tickets/{$ticket->id}/rate", $payload);

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", $payload)
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseCount('support_ticket_rating', 1);
    }

    public function test_communication_quality_persisted_only_when_requested_info_occurred(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'requested_info');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 4,
                'communication_quality' => 3,
                'agents' => [['employee_id' => $agent->id, 'rating' => 5]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'communication_quality' => 3,
        ]);
    }

    public function test_communication_quality_dropped_when_no_additional_contact(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 4,
                'communication_quality' => 5,
                'agents' => [['employee_id' => $agent->id, 'rating' => 5]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'communication_quality' => null,
        ]);
    }

    public function test_degree_of_resolution_persisted_only_when_outcome_is_partial(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'partial');
        $this->addWorkLog($ticket, $agent, 'closed_partial');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 3,
                'degree_of_resolution' => 2,
                'agents' => [['employee_id' => $agent->id, 'rating' => 3]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'degree_of_resolution' => 2,
        ]);
    }

    public function test_rating_rejects_agent_who_did_not_work_on_ticket(): void
    {
        $customer = $this->makeCustomer();
        $worker = $this->makeAgent('Worker', 'One');
        $stranger = $this->makeAgent('Stranger', 'Two');

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $worker, 'closed_success');

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'agents' => [['employee_id' => $stranger->id, 'rating' => 4]],
            ])
            ->assertSessionHasErrors('agents');

        $this->assertDatabaseCount('support_ticket_rating', 0);
    }

    public function test_each_rating_dimension_is_constrained_to_1_to_5(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 7,
                'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
            ])
            ->assertSessionHasErrors('resolution_speed');
    }

    public function test_completing_ticket_without_prior_work_log_records_closing_agent(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'open');

        $this->actingAs($agent)
            ->post("/zaposleni/podrska/{$ticket->id}/complete", [
                'outcome' => 'success',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_work_log', [
            'support_ticket_id' => $ticket->id,
            'employee_id' => $agent->id,
            'action' => 'closed_success',
        ]);
    }
}