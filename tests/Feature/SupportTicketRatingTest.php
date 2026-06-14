<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SupportTicket;
use App\Models\SupportTicketWorkLog;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
                'degree_of_resolution' => 4,
                'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'resolution_speed' => 5,
            'communication_quality' => null,
            'degree_of_resolution' => 4,
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
                'degree_of_resolution' => 4,
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
                'degree_of_resolution' => 4,
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
            'degree_of_resolution' => 4,
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
                'degree_of_resolution' => 4,
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
                'degree_of_resolution' => 4,
                'communication_quality' => 5,
                'agents' => [['employee_id' => $agent->id, 'rating' => 5]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'communication_quality' => null,
        ]);
    }

    public function test_degree_of_resolution_is_required_for_every_outcome(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 4,
                'agents' => [['employee_id' => $agent->id, 'rating' => 5]],
            ])
            ->assertSessionHasErrors('degree_of_resolution');

        $this->assertDatabaseCount('support_ticket_rating', 0);
    }

    public function test_degree_of_resolution_persisted_for_any_outcome(): void
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
                'degree_of_resolution' => 4,
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

    public function test_customer_can_skip_an_agent_and_rate_only_the_others(): void
    {
        $customer = $this->makeCustomer();
        $rated = $this->makeAgent('Rated', 'One');
        $skipped = $this->makeAgent('Skipped', 'Two');

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $rated, 'taken_over');
        $this->addWorkLog($ticket, $skipped, 'closed_success');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 4,
                'degree_of_resolution' => 4,
                'agents' => [['employee_id' => $rated->id, 'rating' => 5]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating_agent', [
            'employee_id' => $rated->id,
            'rating' => 5,
        ]);
        $this->assertDatabaseMissing('support_ticket_rating_agent', [
            'employee_id' => $skipped->id,
        ]);
    }

    public function test_customer_can_submit_with_no_agent_ratings(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 4,
                'degree_of_resolution' => 4,
                'agents' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'resolution_speed' => 4,
        ]);
        $this->assertDatabaseCount('support_ticket_rating_agent', 0);
    }

    public function test_comments_are_persisted_per_agent_and_per_category(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'requested_info');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'resolution_speed_comment' => 'Brzo i efikasno.',
                'communication_quality' => 4,
                'communication_quality_comment' => 'Mogla je biti jasnija.',
                'degree_of_resolution' => 5,
                'degree_of_resolution_comment' => 'Problem u potpunosti rešen.',
                'agents' => [[
                    'employee_id' => $agent->id,
                    'rating' => 5,
                    'comment' => 'Veoma profesionalan.',
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'resolution_speed_comment' => 'Brzo i efikasno.',
            'communication_quality_comment' => 'Mogla je biti jasnija.',
            'degree_of_resolution_comment' => 'Problem u potpunosti rešen.',
        ]);
        $this->assertDatabaseHas('support_ticket_rating_agent', [
            'employee_id' => $agent->id,
            'comment' => 'Veoma profesionalan.',
        ]);
    }

    public function test_comments_are_optional_and_blank_becomes_null(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'resolution_speed_comment' => '   ',
                'degree_of_resolution' => 5,
                'degree_of_resolution_comment' => '',
                'agents' => [[
                    'employee_id' => $agent->id,
                    'rating' => 5,
                    'comment' => null,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'resolution_speed_comment' => null,
            'degree_of_resolution_comment' => null,
        ]);
        $this->assertDatabaseHas('support_ticket_rating_agent', [
            'employee_id' => $agent->id,
            'comment' => null,
        ]);
    }

    public function test_comment_over_500_chars_is_rejected(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $tooLong = str_repeat('a', 501);

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'resolution_speed_comment' => $tooLong,
                'degree_of_resolution' => 5,
                'agents' => [['employee_id' => $agent->id, 'rating' => 5]],
            ])
            ->assertSessionHasErrors('resolution_speed_comment');
    }

    public function test_agent_comment_over_500_chars_is_rejected(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $tooLong = str_repeat('b', 501);

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'degree_of_resolution' => 5,
                'agents' => [[
                    'employee_id' => $agent->id,
                    'rating' => 5,
                    'comment' => $tooLong,
                ]],
            ])
            ->assertSessionHasErrors('agents.0.comment');
    }

    public function test_communication_comment_dropped_when_no_additional_contact(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'communication_quality' => 5,
                'communication_quality_comment' => 'Komentar koji ne treba sačuvati.',
                'degree_of_resolution' => 5,
                'agents' => [['employee_id' => $agent->id, 'rating' => 5]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_rating', [
            'support_ticket_id' => $ticket->id,
            'communication_quality' => null,
            'communication_quality_comment' => null,
        ]);
    }

    public function test_employee_panel_exposes_customer_rating_with_comments(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent('Petar', 'Petrović');
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)->post("/support-tickets/{$ticket->id}/rate", [
            'resolution_speed' => 5,
            'resolution_speed_comment' => 'Veoma brzo.',
            'degree_of_resolution' => 5,
            'agents' => [[
                'employee_id' => $agent->id,
                'rating' => 4,
                'comment' => 'Korektno.',
            ]],
        ]);

        $this->actingAs($agent)
            ->get('/zaposleni/podrska')
            ->assertInertia(fn ($page) => $page
                ->component('zaposleni/podrska')
                ->where('tickets.0.rating.resolution_speed', 5)
                ->where('tickets.0.rating.resolution_speed_comment', 'Veoma brzo.')
                ->where('tickets.0.rating.agents.0.employee_name', 'Petar Petrović')
                ->where('tickets.0.rating.agents.0.comment', 'Korektno.')
            );
    }

    public function test_guest_cannot_rate_a_ticket(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->post("/support-tickets/{$ticket->id}/rate", [
            'resolution_speed' => 5,
            'degree_of_resolution' => 4,
            'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('support_ticket_rating', 0);
    }

    public function test_rating_a_nonexistent_ticket_returns_404(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer)
            ->post('/support-tickets/999999/rate', [
                'resolution_speed' => 5,
                'degree_of_resolution' => 4,
                'agents' => [],
            ])
            ->assertNotFound();
    }

    public function test_omitting_agents_key_entirely_is_rejected(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'degree_of_resolution' => 4,
            ])
            ->assertSessionHasErrors('agents');
    }

    public function test_duplicate_agent_in_payload_is_rejected(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'degree_of_resolution' => 4,
                'agents' => [
                    ['employee_id' => $agent->id, 'rating' => 4],
                    ['employee_id' => $agent->id, 'rating' => 2],
                ],
            ])
            ->assertSessionHasErrors('agents');

        $this->assertDatabaseCount('support_ticket_rating', 0);
    }

    public function test_agent_rating_must_be_at_least_one(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->post("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'degree_of_resolution' => 4,
                'agents' => [['employee_id' => $agent->id, 'rating' => 0]],
            ])
            ->assertSessionHasErrors('agents.0.rating');
    }

    public function test_agent_appearing_in_multiple_work_logs_is_deduplicated(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent('Petar', 'Petrović');
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');

        $this->addWorkLog($ticket, $agent, 'taken_over');
        $this->addWorkLog($ticket, $agent, 'requested_info');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->get('/support-tickets')
            ->assertInertia(fn ($page) => $page
                ->component('kupac/moji-tiketi')
                ->where('tickets.0.agents', fn ($agents) => count($agents) === 1
                    && $agents[0]['employee_id'] === $agent->id
                    && $agents[0]['name'] === 'Petar Petrović'
                )
            );
    }

    public function test_agent_name_falls_back_to_user_name_when_first_last_missing(): void
    {
        $customer = $this->makeCustomer();

        $user = User::factory()->create([
            'first_name' => null,
            'last_name' => null,
            'name' => 'Marko Marković',
        ]);
        Zaposlen::create([
            'user_id' => $user->id,
            'role' => 'agent',
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
        ]);

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $user, 'closed_success');

        $this->actingAs($customer)
            ->get('/support-tickets')
            ->assertInertia(fn ($page) => $page
                ->where('tickets.0.agents.0.name', 'Marko Marković')
            );
    }

    public function test_rating_context_reflects_requested_info_in_payload(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'requested_info');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->get('/support-tickets')
            ->assertInertia(fn ($page) => $page
                ->where('tickets.0.rating_context.has_additional_contact', true)
            );
    }

    private function submitRating(User $customer, SupportTicket $ticket, array $overrides = []): SupportTicket
    {
        $payload = array_merge([
            'resolution_speed' => 5,
            'degree_of_resolution' => 5,
            'agents' => [],
        ], $overrides);

        $this->actingAs($customer)
            ->post("/support-tickets/{$ticket->id}/rate", $payload)
            ->assertRedirect();

        return $ticket->fresh('rating.agentRatings');
    }

    public function test_update_rating_within_72_hours_overwrites_fields(): void
    {
        $customer = $this->makeCustomer();
        $a1 = $this->makeAgent('First', 'Agent');
        $a2 = $this->makeAgent('Second', 'Agent');

        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $a1, 'taken_over');
        $this->addWorkLog($ticket, $a2, 'closed_success');

        $this->submitRating($customer, $ticket, [
            'resolution_speed' => 3,
            'resolution_speed_comment' => 'OK',
            'degree_of_resolution' => 3,
            'agents' => [
                ['employee_id' => $a1->id, 'rating' => 3, 'comment' => 'Stara'],
            ],
        ]);

        $this->actingAs($customer)
            ->patch("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'resolution_speed_comment' => 'Bolje',
                'degree_of_resolution' => 5,
                'agents' => [
                    ['employee_id' => $a1->id, 'rating' => 5, 'comment' => 'Nova'],
                    ['employee_id' => $a2->id, 'rating' => 4, 'comment' => null],
                ],
            ])
            ->assertRedirect();

        $rating = $ticket->fresh('rating.agentRatings')->rating;
        $this->assertSame(5, $rating->resolution_speed);
        $this->assertSame('Bolje', $rating->resolution_speed_comment);
        $this->assertSame(5, $rating->degree_of_resolution);
        $this->assertCount(2, $rating->agentRatings);

        $this->assertDatabaseHas('support_ticket_rating_agent', [
            'support_ticket_rating_id' => $rating->id,
            'employee_id' => $a1->id,
            'rating' => 5,
            'comment' => 'Nova',
        ]);
        $this->assertDatabaseHas('support_ticket_rating_agent', [
            'support_ticket_rating_id' => $rating->id,
            'employee_id' => $a2->id,
            'rating' => 4,
        ]);
    }

    public function test_update_rating_after_72_hours_is_rejected(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->submitRating($customer, $ticket, [
            'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
        ]);

        DB::table('support_ticket_rating')
            ->where('id', $ticket->rating->id)
            ->update(['created_at' => now()->subHours(73)]);

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->patch("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 1,
                'degree_of_resolution' => 1,
                'agents' => [['employee_id' => $agent->id, 'rating' => 1]],
            ])
            ->assertSessionHasErrors('ticket');

        $fresh = $ticket->fresh('rating')->rating;
        $this->assertSame(5, $fresh->resolution_speed);
    }

    public function test_update_rejected_when_no_rating_exists(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->actingAs($customer)
            ->from('/support-tickets')
            ->patch("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 5,
                'degree_of_resolution' => 5,
                'agents' => [['employee_id' => $agent->id, 'rating' => 5]],
            ])
            ->assertSessionHasErrors('ticket');
    }

    public function test_non_owner_cannot_update_someone_elses_rating(): void
    {
        $owner = $this->makeCustomer();
        $other = User::factory()->create();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($owner, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->submitRating($owner, $ticket, [
            'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
        ]);

        $this->actingAs($other)
            ->patch("/support-tickets/{$ticket->id}/rate", [
                'resolution_speed' => 1,
                'degree_of_resolution' => 1,
                'agents' => [],
            ])
            ->assertForbidden();
    }

    public function test_serialized_rating_exposes_editable_until_and_is_editable(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->submitRating($customer, $ticket, [
            'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
        ]);

        $this->actingAs($customer)
            ->get('/support-tickets')
            ->assertInertia(fn ($page) => $page
                ->where('tickets.0.rating.is_editable', true)
                ->has('tickets.0.rating.editable_until')
            );
    }

    public function test_serialized_rating_marks_not_editable_after_window(): void
    {
        $customer = $this->makeCustomer();
        $agent = $this->makeAgent();
        $ticket = $this->makeTicket($customer, status: 'closed', outcome: 'success');
        $this->addWorkLog($ticket, $agent, 'closed_success');

        $this->submitRating($customer, $ticket, [
            'agents' => [['employee_id' => $agent->id, 'rating' => 4]],
        ]);

        DB::table('support_ticket_rating')
            ->where('id', $ticket->rating->id)
            ->update(['created_at' => now()->subHours(73)]);

        $this->actingAs($customer)
            ->get('/support-tickets')
            ->assertInertia(fn ($page) => $page
                ->where('tickets.0.rating.is_editable', false)
            );
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
