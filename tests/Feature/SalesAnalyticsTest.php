<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightTicket;
use App\Models\Plane;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\Route;
use App\Models\TicketClass;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalesAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private int $seat = 0;

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

    private function makeStaff(): User
    {
        $user = User::factory()->create();
        Zaposlen::create([
            'user_id' => $user->id,
            'role' => 'dispatcher',
            'datum_zaposlenja' => now()->toDateString(),
            'status' => 'aktivan',
        ]);
        DB::table('dispatchers')->insert(['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);

        return $user;
    }

    private function makeFlight(User $staff, Carbon $takeoff, int $capacity): Flight
    {
        $this->seq++;

        $plane = Plane::create([
            'reg_number' => 50000 + $this->seq,
            'admin_id' => $staff->id,
            'model' => 'Test Plane',
            'capacity' => $capacity,
            'luxury_level' => 2,
            'range_km' => 5000,
            'max_speed' => 800,
            'repair_service_interval' => 500,
            'model_year' => 2022,
            'status' => 'in_garage',
        ]);

        $from = Airport::create(['iata_code' => 'F'.$this->seq, 'name' => 'From', 'city' => 'From', 'country' => 'X', 'season_type' => 'none']);
        $to = Airport::create(['iata_code' => 'T'.$this->seq, 'name' => 'To', 'city' => 'To', 'country' => 'Y', 'season_type' => 'none']);

        $route = Route::create([
            'starting_airport_id' => $from->id,
            'landing_airport_id' => $to->id,
            'admin_id' => $staff->id,
            'name' => 'Ruta '.$this->seq,
            'distance_km' => 500,
            'estimated_time' => 90,
            'active' => true,
        ]);

        return Flight::create([
            'plane_id' => $plane->id,
            'route_id' => $route->id,
            'dispatcher_id' => $staff->id,
            'expected_takeoff' => $takeoff,
            'expected_arrival' => $takeoff->copy()->addHours(2),
            'status' => 'scheduled',
        ]);
    }

    private function addReservation(Flight $flight, TicketClass $class, User $customer, string $status, int $tickets, float $final): Reservation
    {
        $reservation = Reservation::create([
            'user_id' => $customer->id,
            'total_price' => $final * $tickets,
            'code' => 'SA-'.strtoupper(substr(uniqid(), -6)),
        ]);

        $state = ReservationState::create(['reservation_id' => $reservation->id, 'status' => $status]);
        $reservation->update(['latest_state_id' => $state->id]);

        for ($i = 0; $i < $tickets; $i++) {
            FlightTicket::create([
                'passenger_first_name' => 'Kup',
                'passenger_last_name' => 'Ac',
                'flight_id' => $flight->id,
                'reservation_id' => $reservation->id,
                'ticket_class_id' => $class->id,
                'base_price' => $final * 0.8,
                'final_price' => $final,
                'seat_number' => ++$this->seat,
            ]);
        }

        return $reservation;
    }

    /**
     * One known dataset: a class (Ekonomska), a full flight A with 3 confirmed
     * tickets + 1 cancelled reservation, and an empty flight B — both today.
     *
     * @return array{flight_a: Flight, flight_b: Flight, class: TicketClass}
     */
    private function seedScenario(): array
    {
        $staff = $this->makeStaff();
        $customer = $this->makeCustomer();
        $class = TicketClass::create(['name' => 'Ekonomska', 'multiplier' => 1.0]);

        $today = Carbon::now()->setTime(10, 0);
        $flightA = $this->makeFlight($staff, $today, 100);
        $flightB = $this->makeFlight($staff, $today, 100);

        $this->addReservation($flightA, $class, $customer, 'confirmed', 3, 10000);
        $this->addReservation($flightA, $class, $customer, 'cancelled', 1, 10000);

        return ['flight_a' => $flightA, 'flight_b' => $flightB, 'class' => $class];
    }

    private function makeCustomer(): User
    {
        return User::factory()->create();
    }

    private function range(int $back = 2, int $forward = 2): string
    {
        return 'date_from='.now()->subDays($back)->toDateString().'&date_to='.now()->addDays($forward)->toDateString();
    }

    public function test_admin_can_fetch_sales_analytics_for_valid_range(): void
    {
        $admin = $this->makeAdmin();
        $data = $this->seedScenario();

        $response = $this->actingAs($admin)->getJson('/admin/prodaja/analytics?'.$this->range());

        $response->assertOk()->assertJsonStructure([
            'kpis' => ['tickets_sold', 'revenue', 'cancellation_rate_pct', 'avg_occupancy_pct'],
            'occupancy_by_class' => [['class_id', 'class_name', 'sold', 'total_seats', 'occupancy_pct']],
            'cancellation' => ['total_reservations', 'cancelled_reservations', 'rate_pct'],
            'cancellation_trend' => [['date', 'count']],
            'occupancy_extremes' => ['highest', 'lowest'],
        ]);

        $json = $response->json();

        // 3 non-cancelled tickets sold; the 4th (cancelled reservation) is excluded.
        $this->assertSame(3, $json['kpis']['tickets_sold']);
        $this->assertSame(30000.0, (float) $json['kpis']['revenue']);
        $this->assertSame(50.0, (float) $json['kpis']['cancellation_rate_pct']);
        $this->assertSame(1.5, (float) $json['kpis']['avg_occupancy_pct']);

        $this->assertSame(2, $json['cancellation']['total_reservations']);
        $this->assertSame(1, $json['cancellation']['cancelled_reservations']);
        $this->assertSame(50.0, (float) $json['cancellation']['rate_pct']);

        // Ekonomska: 3 sold of 140 total seats (200 capacity * 0.70 share).
        $eko = collect($json['occupancy_by_class'])->firstWhere('class_name', 'Ekonomska');
        $this->assertSame(3, $eko['sold']);
        $this->assertSame(140, $eko['total_seats']);

        // A (3%) is the fullest, B (0%) the emptiest.
        $this->assertSame($data['flight_a']->id, $json['occupancy_extremes']['highest'][0]['flight_id']);
        $this->assertSame($data['flight_b']->id, $json['occupancy_extremes']['lowest'][0]['flight_id']);

        // The single cancellation lands on today's bucket of the zero-filled trend.
        $today = now()->toDateString();
        $todayRow = collect($json['cancellation_trend'])->firstWhere('date', $today);
        $this->assertSame(1, $todayRow['count']);
    }

    public function test_occupancy_by_class_is_split_into_calendar_seasons(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $customer = $this->makeCustomer();
        $class = TicketClass::create(['name' => 'Ekonomska', 'multiplier' => 1.0]);

        // One flight per calendar season, each on a plane with capacity 100.
        $summer = $this->makeFlight($staff, Carbon::create(2026, 7, 15, 10), 100); // ljeto
        $winter = $this->makeFlight($staff, Carbon::create(2026, 1, 15, 10), 100); // zima
        $off = $this->makeFlight($staff, Carbon::create(2026, 4, 15, 10), 100);    // van sezone

        $this->addReservation($summer, $class, $customer, 'confirmed', 2, 10000);
        $this->addReservation($winter, $class, $customer, 'confirmed', 1, 10000);
        $this->addReservation($off, $class, $customer, 'confirmed', 3, 10000);

        $json = $this->actingAs($admin)
            ->getJson('/admin/prodaja/analytics?date_from=2026-01-01&date_to=2026-12-31')
            ->assertOk()
            ->assertJsonStructure([
                'occupancy_by_class_by_season' => [
                    'leto' => [['class_id', 'class_name', 'sold', 'total_seats', 'occupancy_pct']],
                    'zima' => [['class_id', 'class_name', 'sold', 'total_seats', 'occupancy_pct']],
                    'van_sezone' => [['class_id', 'class_name', 'sold', 'total_seats', 'occupancy_pct']],
                ],
            ])
            ->json();

        $eko = fn (string $season) => collect($json['occupancy_by_class_by_season'][$season])
            ->firstWhere('class_name', 'Ekonomska');

        // Each season counts only its own flight; total seats = 100 capacity * 0.70 share.
        $this->assertSame(2, $eko('leto')['sold']);
        $this->assertSame(70, $eko('leto')['total_seats']);
        $this->assertSame(1, $eko('zima')['sold']);
        $this->assertSame(70, $eko('zima')['total_seats']);
        $this->assertSame(3, $eko('van_sezone')['sold']);
        $this->assertSame(70, $eko('van_sezone')['total_seats']);

        // The seasons partition the period: the unsplit breakdown is their sum.
        $ekoAll = collect($json['occupancy_by_class'])->firstWhere('class_name', 'Ekonomska');
        $this->assertSame(6, $ekoAll['sold']);
        $this->assertSame(210, $ekoAll['total_seats']);
    }

    public function test_empty_range_returns_zeroed_aggregates(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->getJson('/admin/prodaja/analytics?date_from=2020-01-01&date_to=2020-01-07');

        $response->assertOk()
            ->assertJsonPath('kpis.tickets_sold', 0)
            ->assertJsonPath('kpis.revenue', 0)
            ->assertJsonPath('cancellation.total_reservations', 0)
            ->assertJsonPath('cancellation.rate_pct', 0)
            ->assertJsonPath('occupancy_extremes.highest', [])
            ->assertJsonPath('occupancy_extremes.lowest', []);

        // Zero-filled daily trend spans the whole 7-day range.
        $this->assertCount(7, $response->json('cancellation_trend'));
        $this->assertSame(0, $response->json('cancellation_trend.0.count'));
    }

    public function test_non_admin_receives_403(): void
    {
        $this->actingAs($this->makeCustomer())
            ->getJson('/admin/prodaja/analytics?'.$this->range())
            ->assertForbidden();
    }

    public function test_missing_date_params_returns_422(): void
    {
        $this->actingAs($this->makeAdmin())
            ->getJson('/admin/prodaja/analytics')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_from', 'date_to']);
    }

    public function test_date_to_before_date_from_returns_422(): void
    {
        $this->actingAs($this->makeAdmin())
            ->getJson('/admin/prodaja/analytics?date_from=2026-06-30&date_to=2026-06-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to']);
    }

    public function test_admin_can_view_dashboard_page(): void
    {
        $admin = $this->makeAdmin();
        $this->seedScenario();

        $this->actingAs($admin)
            ->get('/admin/prodaja/statistike?'.$this->range())
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/prodaja/statistike')
                ->has('analytics.kpis')
                ->has('analytics.occupancy_by_class')
                ->has('analytics.cancellation')
                ->has('analytics.cancellation_trend')
                ->has('analytics.occupancy_extremes')
                ->where('analytics.kpis.tickets_sold', 3)
                ->has('period.date_from')
                ->has('period.date_to')
            );
    }

    public function test_dashboard_defaults_to_last_30_days_without_params(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/prodaja/statistike')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/prodaja/statistike')
                ->where('period.date_from', now()->subDays(29)->toDateString())
                ->where('period.date_to', now()->toDateString())
            );
    }

    public function test_non_admin_cannot_view_dashboard_page(): void
    {
        $this->actingAs($this->makeCustomer())
            ->get('/admin/prodaja/statistike?'.$this->range())
            ->assertForbidden();
    }
}
