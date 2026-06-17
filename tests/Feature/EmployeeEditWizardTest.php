<?php

namespace Tests\Feature;

use App\Models\TipUgovora;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeEditWizardTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(string $role = 'pilot'): Zaposlen
    {
        $user = User::factory()->create([
            'first_name' => 'Marko',
            'last_name' => 'Markovic',
            'date_of_birth' => '1990-01-01',
            'jmbg' => '1234567890123',
        ]);

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

    /**
     * @return array<string, mixed>
     */
    private function editPayload(Zaposlen $employee, int $tipUgovoraId, string $action): array
    {
        return [
            'first_name' => $employee->user->first_name,
            'last_name' => $employee->user->last_name,
            'email' => $employee->user->email,
            'date_of_birth' => '1990-01-01',
            'jmbg' => '1234567890123',
            'role' => 'pilot',
            'datum_zaposlenja' => now()->subYear()->toDateString(),
            'tip_ugovora_id' => $tipUgovoraId,
            'action' => $action,
        ];
    }

    public function test_continue_advances_the_edit_wizard_to_certificates(): void
    {
        $employee = $this->makeEmployee();
        $tip = TipUgovora::create(['naziv' => 'Stalni', 'opis' => null]);

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}", $this->editPayload($employee, $tip->id, 'continue'))
            ->assertRedirect(route('admin.employee.certificates.edit', $employee->user_id));
    }

    public function test_save_exits_the_edit_wizard_to_the_index(): void
    {
        $employee = $this->makeEmployee();
        $tip = TipUgovora::create(['naziv' => 'Stalni', 'opis' => null]);

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}", $this->editPayload($employee, $tip->id, 'save'))
            ->assertRedirect(route('admin.employee.index'));
    }

    public function test_certificates_save_action_exits_to_index_instead_of_trainings(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/sertifikati", [
                'certificates' => [],
                'action' => 'save',
            ])
            ->assertRedirect(route('admin.employee.index'));
    }

    public function test_certificates_page_reports_edit_mode_without_pending_credentials(): void
    {
        $this->withoutVite();
        $employee = $this->makeEmployee();

        $this->actingAs($this->admin())
            ->get("/admin/employee/{$employee->user_id}/sertifikati")
            ->assertInertia(fn ($page) => $page
                ->component('admin/ZaposlenSertifikati', false)
                ->where('mode', 'edit')
            );
    }

    public function test_certificates_page_reports_create_mode_with_pending_credentials(): void
    {
        $this->withoutVite();
        $employee = $this->makeEmployee();

        $this->actingAs($this->admin())
            ->withSession(['pendingEmployeeCredentials' => ['name' => 'X', 'email' => 'x@y.z', 'password' => 'p']])
            ->get("/admin/employee/{$employee->user_id}/sertifikati")
            ->assertInertia(fn ($page) => $page
                ->component('admin/ZaposlenSertifikati', false)
                ->where('mode', 'create')
            );
    }
}
