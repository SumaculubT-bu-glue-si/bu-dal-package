<?php

namespace YourCompany\GraphQLDAL\Tests\Feature;

use YourCompany\GraphQLDAL\Models\Asset;
use YourCompany\GraphQLDAL\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GraphQLTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /** @test */
    public function it_can_query_assets_via_graphql()
    {
        // Create test data
        Asset::factory()->create([
            'asset_id' => 'TEST-001',
            'type' => 'Laptop',
            'hostname' => 'TEST-LAPTOP',
            'status' => '利用中'
        ]);

        Asset::factory()->create([
            'asset_id' => 'TEST-002',
            'type' => 'Desktop',
            'hostname' => 'TEST-DESKTOP',
            'status' => '保管中'
        ]);

        // Test GraphQL query
        $query = '
            query {
                assets(first: 10) {
                    data {
                        id
                        asset_id
                        type
                        hostname
                        status
                    }
                }
            }
        ';

        $response = $this->postGraphQL([
            'query' => $query
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'assets' => [
                    'data' => [
                        '*' => [
                            'id',
                            'asset_id',
                            'type',
                            'hostname',
                            'status'
                        ]
                    ]
                ]
            ]
        ]);

        // Verify we get our test data
        $data = $response->json('data.assets.data');
        $this->assertCount(2, $data);

        $assetIds = collect($data)->pluck('asset_id')->toArray();
        $this->assertContains('TEST-001', $assetIds);
        $this->assertContains('TEST-002', $assetIds);
    }

    /** @test */
    public function it_can_filter_assets_by_type()
    {
        // Create test data
        Asset::factory()->create(['asset_id' => 'LAPTOP-001', 'type' => 'Laptop']);
        Asset::factory()->create(['asset_id' => 'DESKTOP-001', 'type' => 'Desktop']);
        Asset::factory()->create(['asset_id' => 'LAPTOP-002', 'type' => 'Laptop']);

        // Test GraphQL query with filter
        $query = '
            query {
                assets(first: 10, type: "Laptop") {
                    data {
                        asset_id
                        type
                    }
                }
            }
        ';

        $response = $this->postGraphQL([
            'query' => $query
        ]);

        $response->assertStatus(200);

        $data = $response->json('data.assets.data');
        $this->assertCount(2, $data);

        foreach ($data as $asset) {
            $this->assertEquals('Laptop', $asset['type']);
        }
    }

    /** @test */
    public function it_can_upsert_asset_via_graphql()
    {
        $mutation = '
            mutation {
                upsertAsset(asset: {
                    asset_id: "NEW-ASSET-001"
                    type: "Laptop"
                    hostname: "NEW-LAPTOP"
                    status: "利用中"
                    manufacturer: "Dell"
                    model: "Latitude 5520"
                }) {
                    id
                    asset_id
                    type
                    hostname
                    status
                }
            }
        ';

        $response = $this->postGraphQL([
            'query' => $mutation
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'upsertAsset' => [
                    'asset_id' => 'NEW-ASSET-001',
                    'type' => 'Laptop',
                    'hostname' => 'NEW-LAPTOP',
                    'status' => '利用中'
                ]
            ]
        ]);

        // Verify the asset was created in the database
        $this->assertDatabaseHas('assets', [
            'asset_id' => 'NEW-ASSET-001',
            'type' => 'Laptop',
            'hostname' => 'NEW-LAPTOP',
            'status' => '利用中'
        ]);
    }

    /** @test */
    public function it_can_delete_asset_via_graphql()
    {
        // Create test asset
        $asset = Asset::factory()->create([
            'asset_id' => 'DELETE-TEST-001',
            'type' => 'Laptop'
        ]);

        $mutation = '
            mutation {
                deleteAsset(asset_id: "DELETE-TEST-001")
            }
        ';

        $response = $this->postGraphQL([
            'query' => $mutation
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'deleteAsset' => true
            ]
        ]);

        // Verify the asset was deleted from the database
        $this->assertDatabaseMissing('assets', [
            'asset_id' => 'DELETE-TEST-001'
        ]);
    }
}