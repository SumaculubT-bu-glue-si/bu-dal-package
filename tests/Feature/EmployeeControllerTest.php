<?php

namespace Bu\Server\Tests\Feature;

use Bu\Server\Models\Employee;
use Bu\Server\Models\Location;
use Bu\Server\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $location;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->user = \Bu\Server\Models\User::factory()->create();
        $this->actingAs($this->user);
        
        $this->location = Location::factory()->create();
    }

    /** @test */
    public function it_can_list_employees()
    {
        Employee::factory()->count(5)->create([
            'location_id' => $this->location->id
        ]);

        $response = $this->getJson('/api/employees');

        $response->assertOk()
                ->assertJsonCount(5, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'employee_id',
                            'first_name',
                            'last_name',
                            'email'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_create_an_employee()
    {
        $employeeData = [
            'employee_id' => 'EMP-' . $this->faker->unique()->numberBetween(1000, 9999),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'position' => 'Software Engineer',
            'department' => 'IT',
            'location_id' => $this->location->id,
            'status' => 'active',
            'hire_date' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertCreated()
                ->assertJson([
                    'data' => [
                        'employee_id' => $employeeData['employee_id'],
                        'first_name' => $employeeData['first_name'],
                        'email' => $employeeData['email']
                    ]
                ]);
    }

    /** @test */
    public function it_validates_required_fields_for_employee_creation()
    {
        $response = $this->postJson('/api/employees', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'employee_id', 'first_name', 'last_name', 
                    'email', 'position', 'department', 
                    'location_id', 'status', 'hire_date'
                ]);
    }

    /** @test */
    public function it_can_show_employee_hierarchy()
    {
        $supervisor = Employee::factory()->create([
            'location_id' => $this->location->id
        ]);

        Employee::factory()->count(3)->create([
            'location_id' => $this->location->id,
            'supervisor_id' => $supervisor->id
        ]);

        $response = $this->getJson('/api/employees/hierarchy');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'employee_id',
                            'subordinates' => [
                                '*' => [
                                    'id',
                                    'employee_id'
                                ]
                            ]
                        ]
                    ]
                ]);
    }
}
