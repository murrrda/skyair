<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCertificateTest extends TestCase
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

    private function admin(): User
    {
        return $this->makeEmployee('admin')->user;
    }

    public function test_admin_can_view_the_certificates_step(): void
    {
        $this->withoutVite();
        $employee = $this->makeEmployee();
        CertificateType::factory()->create(['name' => 'Pilotska licenca']);

        $this->actingAs($this->admin())
            ->get("/admin/employee/{$employee->user_id}/sertifikati")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/ZaposlenSertifikati', false)
                ->has('certificateTypes', 1)
                ->where('zaposlen.user_id', $employee->user_id)
            );
    }

    public function test_non_admin_cannot_view_the_certificates_step(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($this->makeEmployee('pilot')->user)
            ->get("/admin/employee/{$employee->user_id}/sertifikati")
            ->assertForbidden();
    }

    public function test_admin_can_add_certificates(): void
    {
        $employee = $this->makeEmployee();
        $type = CertificateType::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/sertifikati", [
                'certificates' => [
                    [
                        'certificate_type_id' => $type->id,
                        'issued_at' => '2025-01-01',
                        'expires_at' => '2030-01-01',
                        'note' => 'Prva licenca',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.employee.index'));

        $this->assertDatabaseHas('certificates', [
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
            'note' => 'Prva licenca',
        ]);
    }

    public function test_same_type_can_be_added_twice_as_a_renewal(): void
    {
        $employee = $this->makeEmployee();
        $type = CertificateType::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/sertifikati", [
                'certificates' => [
                    ['certificate_type_id' => $type->id, 'issued_at' => '2020-01-01', 'expires_at' => '2025-01-01'],
                    ['certificate_type_id' => $type->id, 'issued_at' => '2025-01-01', 'expires_at' => '2030-01-01'],
                ],
            ]);

        $this->assertSame(2, $employee->certificates()->count());
    }

    public function test_expiry_must_be_after_issue_date(): void
    {
        $employee = $this->makeEmployee();
        $type = CertificateType::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/sertifikati", [
                'certificates' => [
                    ['certificate_type_id' => $type->id, 'issued_at' => '2030-01-01', 'expires_at' => '2025-01-01'],
                ],
            ])
            ->assertSessionHasErrors('certificates.0.expires_at');

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_removed_certificate_is_soft_deleted(): void
    {
        $employee = $this->makeEmployee();
        $type = CertificateType::factory()->create();
        $existing = Certificate::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
        ]);

        // Submit an empty list — the existing row should be soft-deleted.
        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/sertifikati", [
                'certificates' => [],
            ]);

        $this->assertSoftDeleted('certificates', ['id' => $existing->id]);
    }

    public function test_existing_certificate_is_updated_not_duplicated(): void
    {
        $employee = $this->makeEmployee();
        $type = CertificateType::factory()->create();
        $existing = Certificate::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
            'note' => 'staro',
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/sertifikati", [
                'certificates' => [
                    [
                        'id' => $existing->id,
                        'certificate_type_id' => $type->id,
                        'issued_at' => '2025-01-01',
                        'expires_at' => '2030-01-01',
                        'note' => 'novo',
                    ],
                ],
            ]);

        $this->assertSame(1, $employee->certificates()->count());
        $this->assertDatabaseHas('certificates', ['id' => $existing->id, 'note' => 'novo']);
    }

    public function test_status_accessor_reflects_expiry(): void
    {
        $type = CertificateType::factory()->create();
        $employee = $this->makeEmployee();

        $active = Certificate::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
            'expires_at' => now()->addYear()->toDateString(),
        ]);
        $expired = Certificate::factory()->expired()->create([
            'zaposlen_user_id' => $employee->user_id,
            'certificate_type_id' => $type->id,
        ]);

        $this->assertSame('active', $active->status);
        $this->assertSame('expired', $expired->status);
    }
}
