<?php

namespace YourCompany\GraphQLDAL\Tests\Unit;

use Tests\TestCase;
use YourCompany\GraphQLDAL\Database\Repositories\AssetRepository;
use YourCompany\GraphQLDAL\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssetRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected AssetRepository $assetRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assetRepository = app(AssetRepository::class);
    }

    /** @test */
    public function it_can_create_an_asset()
    {
        $assetData = [
            'asset_id' => 'TEST-001',
            'type' => 'Laptop',
            'hostname' => 'test-laptop',
            'manufacturer' => 'Dell',
            'model' => 'Latitude 5520',
            'location' => 'Office A',
            'status' => '利用中',
        ];

        $asset = $this->assetRepository->create($assetData);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('TEST-001', $asset->asset_id);
        $this->assertEquals('Laptop', $asset->type);
        $this->assertEquals('test-laptop', $asset->hostname);
    }

    /** @test */
    public function it_can_find_asset_by_asset_id()
    {
        $asset = Asset::factory()->create(['asset_id' => 'TEST-002']);

        $foundAsset = $this->assetRepository->findByAssetId('TEST-002');

        $this->assertInstanceOf(Asset::class, $foundAsset);
        $this->assertEquals($asset->id, $foundAsset->id);
    }

    /** @test */
    public function it_can_upsert_asset_by_asset_id()
    {
        $assetData = [
            'asset_id' => 'TEST-003',
            'type' => 'Desktop',
            'hostname' => 'test-desktop',
            'manufacturer' => 'HP',
            'model' => 'EliteDesk 800',
            'location' => 'Office B',
            'status' => '保管中',
        ];

        $asset = $this->assetRepository->upsertByAssetId($assetData);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('TEST-003', $asset->asset_id);

        // Test update
        $updatedData = array_merge($assetData, ['status' => '利用中']);
        $updatedAsset = $this->assetRepository->upsertByAssetId($updatedData);

        $this->assertEquals('利用中', $updatedAsset->status);
        $this->assertEquals($asset->id, $updatedAsset->id);
    }

    /** @test */
    public function it_can_get_assets_by_location()
    {
        Asset::factory()->create(['location' => 'Office A']);
        Asset::factory()->create(['location' => 'Office A']);
        Asset::factory()->create(['location' => 'Office B']);

        $assets = $this->assetRepository->getByLocation('Office A');

        $this->assertCount(2, $assets);
        $this->assertTrue($assets->every(fn($asset) => $asset->location === 'Office A'));
    }

    /** @test */
    public function it_can_get_available_assets()
    {
        Asset::factory()->create(['status' => '保管中']);
        Asset::factory()->create(['status' => '保管(使用無)']);
        Asset::factory()->create(['status' => '利用中']);
        Asset::factory()->create(['status' => '貸出中']);

        $availableAssets = $this->assetRepository->getAvailable();

        $this->assertCount(2, $availableAssets);
        $this->assertTrue($availableAssets->every(
            fn($asset) =>
            in_array($asset->status, ['保管中', '保管(使用無)', '返却済'])
        ));
    }

    /** @test */
    public function it_can_get_asset_statistics()
    {
        Asset::factory()->create(['type' => 'Laptop', 'status' => '利用中']);
        Asset::factory()->create(['type' => 'Laptop', 'status' => '保管中']);
        Asset::factory()->create(['type' => 'Desktop', 'status' => '利用中']);

        $stats = $this->assetRepository->getStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('assigned', $stats);
        $this->assertArrayHasKey('available', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_status', $stats);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['assigned']);
        $this->assertEquals(1, $stats['available']);
    }
}
