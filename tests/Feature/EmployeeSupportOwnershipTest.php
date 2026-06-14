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

    private function makeNonAgent(string $role): User
    {
        $user = User::factory()->create();
        Zaposlen::create([
            'user_id' => $user->id,
            'role' => $role,
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
        ]);

        return $user;
    }

    public function test_colleagues_list_contains_only_agents(): void
    {
        $me = $this->makeAgent('Me', 'Agent');
        $peer = $this->makeAgent('Peer', 'Agent');
        $admin = $this->makeNonAgent('admin');
        $pilot = $this->makeNonAgent('pilot');
        $dispatcher = $this->makeNonAgent('dispatcher');

        $this->actingAs($me)
            ->get('/zaposleni/podrska')
            ->assertInertia(function ($page) use ($me, $peer, $admin, $pilot, $dispatcher) {
                $ids = collect($page->toArray()['props']['colleagues'])->pluck('id')->all();

                $this->assertContains($me->id, $ids);
                $this->assertContains($peer->id, $ids);
                $this->assertNotContains($admin->id, $ids);
                $this->assertNotContains($pilot->id, $ids);
                $this->assertNotContains($dispatcher->id, $ids);

                return $page;
            });
    }

    public function test_colleagues_ordered_by_open_tickets_ascending(): void
    {
        $customer = $this->makeCustomer();

        $idle = $this->makeAgent('Idle', 'Agent');
        $busy = $this->makeAgent('Busy', 'Agent');
        $medium = $this->makeAgent('Medium', 'Agent');

        // give "busy" 2 open work logs, "medium" 1, "idle" 0
        $this->takeOver($busy, $this->makeTicket($customer, status: 'in_progress'));
        $this->takeOver($busy, $this->makeTicket($customer, status: 'in_progress'));
        $this->takeOver($medium, $this->makeTicket($customer, status: 'in_progress'));

        $this->actingAs($idle)
            ->get('/zaposleni/podrska')
            ->assertInertia(function ($page) use ($idle, $busy, $medium) {
                $orderedIds = collect($page->toArray()['props']['colleagues'])->pluck('id')->all();

                $this->assertSame(
                    [$idle->id, $medium->id, $busy->id],
                    $orderedIds,
                );

                return $page;
            });
    }

    public function test_open_tickets_count_ignores_closed_work_logs(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();

        // Two closed work logs (ended_at set) on different tickets
        $closedTicketA = $this->makeTicket($customer, status: 'closed');
        $closedTicketB = $this->makeTicket($customer, status: 'closed');
        SupportTicketWorkLog::create([
            'support_ticket_id' => $closedTicketA->id,
            'employee_id' => $agent->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'action' => 'closed_success',
        ]);
        SupportTicketWorkLog::create([
            'support_ticket_id' => $closedTicketB->id,
            'employee_id' => $agent->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'action' => 'closed_success',
        ]);

        // One open log on an in-progress ticket
        $openTicket = $this->makeTicket($customer, status: 'in_progress');
        $this->takeOver($agent, $openTicket);

        $this->actingAs($agent)
            ->get('/zaposleni/podrska')
            ->assertInertia(function ($page) use ($agent) {
                $props = $page->toArray()['props'];
                $me = $props['me'];
                $row = collect($props['colleagues'])->firstWhere('id', $agent->id);

                $this->assertSame(1, $me['open_tickets']);
                $this->assertSame(1, $row['open_tickets']);

                return $page;
            });
    }
}
