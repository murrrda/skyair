<?php

namespace Tests\Feature;

use App\Models\EmployeeTraining;
use App\Models\TrainingType;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTrainingTest extends TestCase
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

    public function test_admin_can_view_the_trainings_step(): void
    {
        $this->withoutVite();
        $employee = $this->makeEmployee();
        TrainingType::factory()->create();

        $this->actingAs($this->admin())
            ->get("/admin/employee/{$employee->user_id}/obuke")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/ZaposlenObuke', false)
                ->has('trainingTypes', 1)
                ->where('zaposlen.user_id', $employee->user_id)
            );
    }

    public function test_non_admin_cannot_view_the_trainings_step(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($this->makeEmployee('pilot')->user)
            ->get("/admin/employee/{$employee->user_id}/obuke")
            ->assertForbidden();
    }

    public function test_admin_can_add_trainings(): void
    {
        $employee = $this->makeEmployee();
        $type = TrainingType::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/obuke", [
                'trainings' => [
                    [
                        'training_type_id' => $type->id,
                        'started_at' => '2025-01-01',
                        'finished_at' => '2025-01-05',
                        'note' => 'Instruktor: Capt. Đorđić',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.employee.index'));

        $this->assertDatabaseHas('employee_trainings', [
            'zaposlen_user_id' => $employee->user_id,
            'training_type_id' => $type->id,
            'note' => 'Instruktor: Capt. Đorđić',
        ]);
    }

    public function test_same_training_can_be_recorded_twice_as_recertification(): void
    {
        $employee = $this->makeEmployee();
        $type = TrainingType::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/obuke", [
                'trainings' => [
                    ['training_type_id' => $type->id, 'started_at' => '2020-01-01', 'finished_at' => '2020-01-05'],
                    ['training_type_id' => $type->id, 'started_at' => '2025-01-01', 'finished_at' => '2025-01-05'],
                ],
            ]);

        $this->assertSame(2, $employee->trainings()->count());
    }

    public function test_finish_date_cannot_precede_start_date(): void
    {
        $employee = $this->makeEmployee();
        $type = TrainingType::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/obuke", [
                'trainings' => [
                    ['training_type_id' => $type->id, 'started_at' => '2025-01-10', 'finished_at' => '2025-01-01'],
                ],
            ])
            ->assertSessionHasErrors('trainings.0.finished_at');

        $this->assertDatabaseCount('employee_trainings', 0);
    }

    public function test_same_day_training_is_allowed(): void
    {
        $employee = $this->makeEmployee();
        $type = TrainingType::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/obuke", [
                'trainings' => [
                    ['training_type_id' => $type->id, 'started_at' => '2025-01-01', 'finished_at' => '2025-01-01'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $employee->trainings()->count());
    }

    public function test_removed_training_is_soft_deleted(): void
    {
        $employee = $this->makeEmployee();
        $type = TrainingType::factory()->create();
        $existing = EmployeeTraining::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'training_type_id' => $type->id,
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/employee/{$employee->user_id}/obuke", [
                'trainings' => [],
            ]);

        $this->assertSoftDeleted('employee_trainings', ['id' => $existing->id]);
    }

    public function test_finishing_the_wizard_surfaces_pending_credentials(): void
    {
        $employee = $this->makeEmployee();
        $type = TrainingType::factory()->create();

        $this->actingAs($this->admin())
            ->withSession(['pendingEmployeeCredentials' => ['name' => 'X', 'email' => 'x@y.z', 'password' => 'secret']])
            ->put("/admin/employee/{$employee->user_id}/obuke", [
                'trainings' => [
                    ['training_type_id' => $type->id, 'started_at' => '2025-01-01', 'finished_at' => '2025-01-05'],
                ],
            ])
            ->assertSessionHas('newEmployeeCredentials');
    }
}
