<?php

namespace Tests\Feature;

use App\Models\EmployeeTraining;
use App\Models\TrainingType;
use App\Models\User;
use App\Models\Zaposlen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyTrainingsTest extends TestCase
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

    public function test_employee_can_view_their_own_trainings(): void
    {
        $this->withoutVite();
        $employee = $this->makeEmployee();
        $type = TrainingType::factory()->create(['name' => 'Obuka na simulatoru — Full Flight']);
        EmployeeTraining::factory()->create([
            'zaposlen_user_id' => $employee->user_id,
            'training_type_id' => $type->id,
        ]);

        $this->actingAs($employee->user)
            ->get('/employee/trainings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('employee/trainings', false)
                ->has('trainings', 1)
                ->where('trainings.0.type', 'Obuka na simulatoru — Full Flight')
            );
    }

    public function test_customer_cannot_view_employee_trainings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/employee/trainings')
            ->assertForbidden();
    }

    public function test_employee_only_sees_their_own_trainings(): void
    {
        $this->withoutVite();
        $me = $this->makeEmployee();
        $other = $this->makeEmployee();
        $type = TrainingType::factory()->create();

        EmployeeTraining::factory()->create(['zaposlen_user_id' => $me->user_id, 'training_type_id' => $type->id]);
        EmployeeTraining::factory()->count(3)->create(['zaposlen_user_id' => $other->user_id, 'training_type_id' => $type->id]);

        $this->actingAs($me->user)
            ->get('/employee/trainings')
            ->assertInertia(fn ($page) => $page->has('trainings', 1));
    }
}
