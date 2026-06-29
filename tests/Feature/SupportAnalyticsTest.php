<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        Zaposlen::create([
            'user_id' => $user->id,
            'role' => 'admin',
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
        ]);

        return $user;
    }

    private function makeCustomer(): User
    {
        return User::factory()->create();
    }

    private function makeTicket(User $customer, string $status = 'open', ?string $outcome = null, ?string $createdAt = null): SupportTicket
    {
        $category = Category::firstOrCreate(['name' => 'Test kategorija']);

        $ticket = SupportTicket::create([
            'user_id' => $customer->id,
            'category_id' => $category->id,
            'description' => 'Test problem opis',
            'number' => 'ST-'.uniqid(),
            'status' => $status,
            'priority' => 'medium',
            'outcome' => $outcome,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);

        if ($createdAt) {
            $ticket->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $ticket;
    }

    public function test_admin_can_fetch_analytics_for_valid_range(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $this->makeTicket($customer, 'open');
        $this->makeTicket($customer, 'closed', 'success');
        $this->makeTicket($customer, 'closed', 'fail');

        $response = $this->actingAs($admin)
            ->getJson('/admin/podrska/analytics?date_from='.now()->subDays(7)->toDateString().'&date_to='.now()->toDateString());

        $response->assertOk()
            ->assertJsonStructure([
                'total_tickets',
                'open_tickets',
                'tickets_by_category' => [['category_id', 'category_name', 'count', 'percentage']],
                'resolution_time_by_category',
                'outcome_summary' => ['success_count', 'partial_count', 'fail_count', 'success_pct', 'partial_pct', 'fail_pct'],
                'previous_period_outcome_summary' => ['success_count', 'partial_count', 'fail_count', 'success_pct', 'partial_pct', 'fail_pct'],
                'top_flights_by_issues',
                'daily_counts' => [['date', 'count']],
            ]);

        $response->assertJsonPath('total_tickets', 3);
        $response->assertJsonPath('open_tickets', 1);
        $response->assertJsonPath('outcome_summary.success_count', 1);
        $response->assertJsonPath('outcome_summary.fail_count', 1);
        $response->assertJsonPath('outcome_summary.success_pct', 50);
    }

    public function test_empty_range_returns_zero_counts(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->getJson('/admin/podrska/analytics?date_from=2020-01-01&date_to=2020-01-31');

        $response->assertOk()
            ->assertJsonPath('total_tickets', 0)
            ->assertJsonPath('open_tickets', 0)
            ->assertJsonPath('top_flights_by_issues', [])
            ->assertJsonPath('outcome_summary.success_count', 0);

        $this->assertCount(31, $response->json('daily_counts'));
        $this->assertSame(0, $response->json('daily_counts.0.count'));
    }

    public function test_draft_tickets_are_excluded_from_analytics(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $this->makeTicket($customer, 'draft');
        $this->makeTicket($customer, 'open');

        $response = $this->actingAs($admin)
            ->getJson('/admin/podrska/analytics?date_from='.now()->subDays(1)->toDateString().'&date_to='.now()->toDateString());

        $response->assertOk()->assertJsonPath('total_tickets', 1);
    }

    public function test_non_admin_receives_403(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer)
            ->getJson('/admin/podrska/analytics?date_from=2026-01-01&date_to=2026-01-31')
            ->assertForbidden();
    }

    public function test_missing_date_params_returns_422(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/admin/podrska/analytics')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_from', 'date_to']);
    }

    public function test_date_to_before_date_from_returns_422(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/admin/podrska/analytics?date_from=2026-06-30&date_to=2026-06-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to']);
    }

    public function test_daily_counts_covers_full_range(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->getJson('/admin/podrska/analytics?date_from=2026-01-01&date_to=2026-01-07');

        $response->assertOk();
        $this->assertCount(7, $response->json('daily_counts'));
        $this->assertSame('2026-01-01', $response->json('daily_counts.0.date'));
        $this->assertSame('2026-01-07', $response->json('daily_counts.6.date'));
    }

    public function test_admin_can_download_pdf_report(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $this->makeTicket($customer, 'closed', 'success', '2026-06-10 10:00:00');
        $this->makeTicket($customer, 'closed', 'partial', '2026-06-11 10:00:00');

        $response = $this->actingAs($admin)
            ->get('/admin/podrska/statistike/pdf?date_from=2026-06-01&date_to=2026-06-30');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'podrska-izvestaj-2026-06-30.pdf',
            $response->headers->get('Content-Disposition') ?? '',
        );
    }

    public function test_non_admin_cannot_download_pdf_report(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer)
            ->get('/admin/podrska/statistike/pdf?date_from=2026-06-01&date_to=2026-06-30')
            ->assertForbidden();
    }
}
