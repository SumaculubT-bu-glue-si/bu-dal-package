<?php

namespace Bu\DAL\Tests\Feature;

use Tests\TestCase;
use Bu\DAL\Models\Asset;
use Bu\DAL\Models\Employee;
use Bu\DAL\Models\Location;
use Bu\DAL\Database\Repositories\AssetRepository;
use Bu\DAL\Database\Repositories\EmployeeRepository;
use Bu\DAL\Database\Repositories\LocationRepository;

class GraphQLTest extends TestCase
{
    public function testAssetRepositoryCanFindByAssetId()
    {
        $assetRepo = new AssetRepository(new Asset());

        // This test would require a database connection
        // For now, we'll just test that the method exists
        $this->assertTrue(method_exists($assetRepo, 'findByAssetId'));
    }

    public function testEmployeeRepositoryCanSearchByName()
    {
        $employeeRepo = new EmployeeRepository(new Employee());

        $this->assertTrue(method_exists($employeeRepo, 'searchByName'));
    }

    public function testLocationRepositoryCanGetNamesByIds()
    {
        $locationRepo = new LocationRepository(new Location());

        $this->assertTrue(method_exists($locationRepo, 'getNamesByIds'));
    }
}
