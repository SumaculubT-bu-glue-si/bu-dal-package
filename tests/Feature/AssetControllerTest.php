<?php

namespace Bu\Server\Tests\Feature;

use Bu\Server\Models\Asset;
use Bu\Server\Models\Location;
use Bu\Server\Models\Employee;
use Bu\Server\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        // Create test user and authenticate
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_assets()
    {
        $assets = Asset::factory()->count(5)->create();

        $response = $this->getJson('/api/assets');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'asset_id',
                        'type',
                        'manufacturer',
                        'model',
                        'status'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_create_an_asset()
    {
        $location = Location::factory()->create();

        $assetData = [
            'asset_id' => 'AST-' . $this->faker->unique()->numberBetween(1000, 9999),
            'type' => 'pc',
            'manufacturer' => 'Dell',
            'model' => 'Latitude 5520',
            'serial_number' => $this->faker->unique()->uuid,
            'status' => 'active',
            'location_id' => $location->id
        ];

        $response = $this->postJson('/api/assets', $assetData);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'asset_id' => $assetData['asset_id'],
                    'type' => $assetData['type'],
                    'manufacturer' => $assetData['manufacturer']
                ]
            ]);
    }

    /** @test */
    public function it_validates_required_fields_for_asset_creation()
    {
        $response = $this->postJson('/api/assets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['asset_id', 'type', 'manufacturer', 'model', 'serial_number', 'location_id']);
    }

    /** @test */
    public function it_can_update_an_asset()
    {
        $asset = Asset::factory()->create();
        $newData = ['manufacturer' => 'HP', 'model' => 'EliteBook'];

        $response = $this->putJson("/api/assets/{$asset->id}", array_merge($asset->toArray(), $newData));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'manufacturer' => 'HP',
                    'model' => 'EliteBook'
                ]
            ]);
    }

    /** @test */
    public function it_can_delete_an_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->deleteJson("/api/assets/{$asset->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    /** @test */
    public function it_can_filter_assets_by_type()
    {
        Asset::factory()->count(3)->create(['type' => 'pc']);
        Asset::factory()->count(2)->create(['type' => 'monitor']);

        $response = $this->getJson('/api/assets/type/pc');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }
}