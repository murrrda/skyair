<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SupportTicket;
use App\Models\SupportTicketWorkLog;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSupportOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        return User::factory()->create(['first_name' => 'Kupac', 'last_name' => 'Testić']);
    }

    private function makeAgent(string $first = 'Agent', string $last = 'Smith'): User
    {
        $user = User::factory()->create(['first_name' => $first, 'last_name' => $last]);
        Zaposlen::create([
            'user_id' => $user->id,
            'role' => 'agent',
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
        ]);

        return $user;
    }

    private function makeTicket(User $customer, string $status = 'open'): SupportTicket
    {
        $category = Category::firstOrCreate(['name' => 'Test kategorija']);

        return SupportTicket::create([
            'user_id' => $customer->id,
            'category_id' => $category->id,
            'description' => 'Opis',
            'number' => 'ST-OWN-'.uniqid(),
            'status' => $status,
            'priority' => 'medium',
        ]);
    }

    private function takeOver(User $agent, SupportTicket $ticket): SupportTicketWorkLog
    {
        return SupportTicketWorkLog::create([
            'support_ticket_id' => $ticket->id,
            'employee_id' => $agent->id,
            'started_at' => now(),
            'action' => 'taken_over',
        ]);
    }

    public function test_other_employee_cannot_take_over_a_ticket_already_owned(): void
    {
        $customer = $this->makeCustomer();
        $owner = $this->makeAgent('Owner', 'One');
        $thief = $this->makeAgent('Thief', 'Two');
        $ticket = $this->makeTicket($customer, status: 'in_progress');
        $this->takeOver($owner, $ticket);

        $this->actingAs($thief)
            ->from('/zaposleni/podrska')
            ->post("/zaposleni/podrska/{$ticket->id}/take")
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseHas('support_ticket_work_log', [
            'support_ticket_id' => $ticket->id,
            'employee_id' => $owner->id,
            'ended_at' => null,
        ]);
        $this->assertDatabaseMissing('support_ticket_work_log', [
            'support_ticket_id' => $ticket->id,
            'employee_id' => $thief->id,
        ]);
    }

    public function test_employee_can_take_over_an_unowned_ticket(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'open');

        $this->actingAs($agent)
            ->post("/zaposleni/podrska/{$ticket->id}/take")
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_work_log', [
            'support_ticket_id' => $ticket->id,
            'employee_id' => $agent->id,
            'ended_at' => null,
        ]);
    }

    public function test_only_current_owner_can_transfer(): void
    {
        $customer = $this->makeCustomer();
        $owner = $this->makeAgent('Owner', 'One');
        $other = $this->makeAgent('Other', 'Two');
        $target = $this->makeAgent('Target', 'Three');
        $ticket = $this->makeTicket($customer, status: 'in_progress');
        $this->takeOver($owner, $ticket);

        $this->actingAs($other)
            ->from('/zaposleni/podrska')
            ->post("/zaposleni/podrska/{$ticket->id}/transfer", ['to_employee_id' => $target->id])
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseMissing('support_ticket_work_log', [
            'support_ticket_id' => $ticket->id,
            'employee_id' => $target->id,
        ]);

        $this->actingAs($owner)
            ->post("/zaposleni/podrska/{$ticket->id}/transfer", ['to_employee_id' => $target->id])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_work_log', [
            'support_ticket_id' => $ticket->id,
            'employee_id' => $target->id,
            'action' => 'received_transfer',
            'ended_at' => null,
        ]);
    }

    public function test_only_current_owner_can_request_info(): void
    {
        $customer = $this->makeCustomer();
        $owner = $this->makeAgent('Owner', 'One');
        $other = $this->makeAgent('Other', 'Two');
        $ticket = $this->makeTicket($customer, status: 'in_progress');
        $this->takeOver($owner, $ticket);

        $this->actingAs($other)
            ->from('/zaposleni/podrska')
            ->post("/zaposleni/podrska/{$ticket->id}/request-info")
            ->assertSessionHasErrors('ticket');

        $this->assertSame('in_progress', $ticket->fresh()->status);
    }

    public function test_only_current_owner_can_complete(): void
    {
        $customer = $this->makeCustomer();
        $owner = $this->makeAgent('Owner', 'One');
        $other = $this->makeAgent('Other', 'Two');
        $ticket = $this->makeTicket($customer, status: 'in_progress');
        $this->takeOver($owner, $ticket);

        $this->actingAs($other)
            ->from('/zaposleni/podrska')
            ->post("/zaposleni/podrska/{$ticket->id}/complete", ['outcome' => 'success'])
            ->assertSessionHasErrors('ticket');

        $this->assertSame('in_progress', $ticket->fresh()->status);
    }

    public function test_owner_can_take_again_idempotently(): void
    {
        $customer = $this->makeCustomer();
        $owner = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'in_progress');
        $this->takeOver($owner, $ticket);

        $this->actingAs($owner)
            ->post("/zaposleni/podrska/{$ticket->id}/take")
            ->assertRedirect();

        $openCount = SupportTicketWorkLog::where('support_ticket_id', $ticket->id)
            ->whereNull('ended_at')
            ->count();
        $this->assertSame(1, $openCount);
    }
}