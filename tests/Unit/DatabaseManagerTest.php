<?php

namespace Bu\DAL\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Bu\DAL\Database\DatabaseManager;
use Bu\DAL\Exceptions\DatabaseException;

class DatabaseManagerTest extends TestCase
{
    public function testDatabaseManagerCanBeInstantiated()
    {
        $dbManager = new DatabaseManager();
        $this->assertInstanceOf(DatabaseManager::class, $dbManager);
    }

    public function testGetAvailableConnections()
    {
        $dbManager = new DatabaseManager();
        $connections = $dbManager->getAvailableConnections();

        $this->assertIsArray($connections);
        $this->assertContains('mysql', $connections);
    }

    public function testGetConnectionInfo()
    {
        $dbManager = new DatabaseManager();
        $info = $dbManager->getConnectionInfo('mysql');

        $this->assertIsArray($info);
        $this->assertEquals('mysql', $info['name']);
        $this->assertEquals('mysql', $info['driver']);
    }
}
