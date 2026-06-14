<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeEmployee(string $role): User
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

    // ── Inertia shared `auth.role` prop ─────────────────────────────────

    public function test_shared_auth_role_is_null_for_guests(): void
    {
        $this->get('/')
            ->assertInertia(fn ($page) => $page->where('auth.role', null));
    }

    public function test_shared_auth_role_is_kupac_for_non_employee_users(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/support-tickets')
            ->assertInertia(fn ($page) => $page->where('auth.role', 'kupac'));
    }

    public function test_shared_auth_role_is_agent_for_zaposlen_with_agent_role(): void
    {
        $this->actingAs($this->makeEmployee('agent'))
            ->get('/zaposleni/podrska')
            ->assertInertia(fn ($page) => $page->where('auth.role', 'agent'));
    }

    public function test_shared_auth_role_is_admin_for_zaposlen_with_admin_role(): void
    {
        $this->actingAs($this->makeEmployee('admin'))
            ->get('/admin')
            ->assertInertia(fn ($page) => $page->where('auth.role', 'admin'));
    }

    // ── /support-tickets (is-kupac) ─────────────────────────────────────

    public function test_guest_cannot_reach_support_tickets(): void
    {
        $this->get('/support-tickets')->assertRedirect(route('login'));
    }

    public function test_customer_can_reach_support_tickets(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/support-tickets')
            ->assertOk();
    }

    public function test_agent_cannot_reach_support_tickets(): void
    {
        $this->actingAs($this->makeEmployee('agent'))
            ->get('/support-tickets')
            ->assertForbidden();
    }

    public function test_pilot_cannot_reach_support_tickets(): void
    {
        $this->actingAs($this->makeEmployee('pilot'))
            ->get('/support-tickets')
            ->assertForbidden();
    }

    public function test_admin_cannot_reach_support_tickets(): void
    {
        $this->actingAs($this->makeEmployee('admin'))
            ->get('/support-tickets')
            ->assertForbidden();
    }

    // ── /zaposleni/podrska (is-agent) ───────────────────────────────────

    public function test_guest_cannot_reach_support_panel(): void
    {
        $this->get('/zaposleni/podrska')->assertRedirect(route('login'));
    }

    public function test_customer_cannot_reach_support_panel(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/zaposleni/podrska')
            ->assertForbidden();
    }

    public function test_agent_can_reach_support_panel(): void
    {
        $this->actingAs($this->makeEmployee('agent'))
            ->get('/zaposleni/podrska')
            ->assertOk();
    }

    public function test_admin_can_reach_support_panel(): void
    {
        $this->actingAs($this->makeEmployee('admin'))
            ->get('/zaposleni/podrska')
            ->assertOk();
    }

    public function test_pilot_cannot_reach_support_panel(): void
    {
        $this->actingAs($this->makeEmployee('pilot'))
            ->get('/zaposleni/podrska')
            ->assertForbidden();
    }

    // ── /admin (is-admin) ───────────────────────────────────────────────

    public function test_admin_can_reach_admin_panel(): void
    {
        $this->actingAs($this->makeEmployee('admin'))
            ->get('/admin')
            ->assertOk();
    }

    public function test_agent_cannot_reach_admin_panel(): void
    {
        $this->actingAs($this->makeEmployee('agent'))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_customer_cannot_reach_admin_panel(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/admin')
            ->assertForbidden();
    }

    // ── /employee/my-flights (is-zaposlen) ──────────────────────────────

    public function test_customer_cannot_reach_employee_routes(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/employee/my-flights')
            ->assertForbidden();
    }

    public function test_zaposlen_can_reach_employee_routes(): void
    {
        $this->actingAs($this->makeEmployee('pilot'))
            ->get('/employee/my-flights')
            ->assertOk();
    }

    // ── customer cannot use any /kupac/* protected route ────────────────

    public function test_agent_cannot_reach_customer_reservation_routes(): void
    {
        $this->actingAs($this->makeEmployee('agent'))
            ->get('/kupac/moji-letovi')
            ->assertForbidden();
    }
}
