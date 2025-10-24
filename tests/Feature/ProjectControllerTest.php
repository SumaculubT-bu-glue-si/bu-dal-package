<?php

namespace Bu\Server\Tests\Feature;

use Bu\Server\Models\Project;
use Bu\Server\Models\Employee;
use Bu\Server\Models\Location;
use Bu\Server\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $manager;
    protected $location;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        $this->location = Location::factory()->create();
        $this->manager = Employee::factory()->create([
            'location_id' => $this->location->id
        ]);
    }

    /** @test */
    public function it_can_list_projects()
    {
        Project::factory()->count(5)->create([
            'manager_id' => $this->manager->id
        ])->each(function ($project) {
            $project->locations()->attach($this->location->id);
        });

        $response = $this->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'status',
                        'priority'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_create_a_project()
    {
        $projectData = [
            'name' => 'Test Project',
            'code' => 'PRJ-' . $this->faker->unique()->numberBetween(1000, 9999),
            'description' => 'Test project description',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(6)->format('Y-m-d'),
            'status' => 'planning',
            'priority' => 'medium',
            'manager_id' => $this->manager->id,
            'location_ids' => [$this->location->id]
        ];

        $response = $this->postJson('/api/projects', $projectData);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => $projectData['name'],
                    'code' => $projectData['code'],
                    'status' => $projectData['status']
                ]
            ]);
    }

    /** @test */
    public function it_validates_required_fields_for_project_creation()
    {
        $response = $this->postJson('/api/projects', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'code',
                'description',
                'start_date',
                'end_date',
                'status',
                'priority',
                'manager_id',
                'location_ids'
            ]);
    }

    /** @test */
    public function it_can_get_project_statistics()
    {
        Project::factory()->count(3)->create([
            'manager_id' => $this->manager->id,
            'status' => 'active'
        ]);

        Project::factory()->count(2)->create([
            'manager_id' => $this->manager->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson('/api/projects/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'by_status',
                    'by_priority'
                ]
            ]);
    }
}