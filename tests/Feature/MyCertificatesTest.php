<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCertificatesTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(string $role = 'pilot'): Zaposlen
    {
        $user = User::factory()->create();

        return Zaposlen::create([
            'user_id' => $user->id,
            'role' => $role,
            'datum_zaposlenja' => now()->subYear()->toDateString(),
            'status' => 'aktivan',
        ]);
    }

    public function test_employee_can_view_their_own_certificates(): void
    {
        $this->withoutVite();
        $employee = $this->makeEmployee();
        $type = CertificateType::factory()->create(['name' => 'Pilotska licenca']);
        Certificate::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
        ]);

        $this->actingAs($employee->user)
            ->get('/employee/certificates')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('employee/certificates', false)
                ->has('certificates', 1)
                ->where('certificates.0.type', 'Pilotska licenca')
            );
    }

    public function test_customer_cannot_view_employee_certificates(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/employee/certificates')
            ->assertForbidden();
    }

    public function test_employee_only_sees_their_own_certificates(): void
    {
        $this->withoutVite();
        $me = $this->makeEmployee();
        $other = $this->makeEmployee();
        $type = CertificateType::factory()->create();

        Certificate::factory()->create(['zaposlen_user_id' => $me->user_id, 'certificate_type_id' => $type->id]);
        Certificate::factory()->count(2)->create(['zaposlen_user_id' => $other->user_id, 'certificate_type_id' => $type->id]);

        $this->actingAs($me->user)
            ->get('/employee/certificates')
            ->assertInertia(fn ($page) => $page->has('certificates', 1));
    }

    public function test_status_is_computed_from_expiry(): void
    {
        $this->withoutVite();
        $employee = $this->makeEmployee();
        $type = CertificateType::factory()->create();

        Certificate::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
            'issued_at' => now()->subYears(2)->toDateString(),
            'expires_at' => now()->subDay()->toDateString(),
        ]);
        Certificate::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addDays(10)->toDateString(),
        ]);
        Certificate::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addYears(2)->toDateString(),
        ]);

        $response = $this->actingAs($employee->user)->get('/employee/certificates');

        // Ordered by expires_at desc: valid (+2y), expiring (+10d), expired (-1d)
        $response->assertInertia(fn ($page) => $page
            ->where('certificates.0.status', 'valid')
            ->where('certificates.1.status', 'expiring')
            ->where('certificates.2.status', 'expired')
        );
    }
}
