<?php
declare(strict_types=1);

namespace CoreCart\Tests\Integration;

use CoreCart\System\Repository\SettingRepository;
use CoreCart\System\Service\SettingService;
use CoreCart\Tests\Helper\TestDatabase;
use PHPUnit\Framework\TestCase;

class SettingTest extends TestCase
{
    private TestDatabase $db;
    private SettingService $service;

    protected function setUp(): void
    {
        $this->db = new TestDatabase();
        $repo = new SettingRepository($this->db);
        $this->service = new SettingService($repo);
    }

    public function testSetAndGet(): void
    {
        $this->service->set('store', 'name', 'My Store');
        $this->assertEquals('My Store', $this->service->get('store', 'name'));
    }

    public function testGetDefault(): void
    {
        $this->assertEquals('Default', $this->service->get('store', 'missing', 'Default'));
    }

    public function testGetGroup(): void
    {
        $this->service->set('meta', 'title', 'Shop Title');
        $this->service->set('meta', 'description', 'Shop Description');

        $group = $this->service->getGroup('meta');
        $this->assertEquals('Shop Title', $group['title']);
        $this->assertEquals('Shop Description', $group['description']);
    }

    public function testSetGroup(): void
    {
        $this->service->setGroup('mail', [
            'protocol' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => '587',
        ]);

        $this->assertEquals('smtp', $this->service->get('mail', 'protocol'));
        $this->assertEquals('smtp.example.com', $this->service->get('mail', 'host'));
        $this->assertEquals('587', $this->service->get('mail', 'port'));
    }

    public function testDelete(): void
    {
        $this->service->set('store', 'temp', 'value');
        $this->service->delete('store', 'temp');
        $this->assertEquals('', $this->service->get('store', 'temp'));
    }

    public function testGetStoreName(): void
    {
        $this->assertEquals('CoreCart', $this->service->getStoreName());

        $this->service->set('store', 'name', 'Custom Store');
        $this->assertEquals('Custom Store', $this->service->getStoreName());
    }

    public function testIsMaintenanceMode(): void
    {
        $this->assertFalse($this->service->isMaintenanceMode());

        $this->service->set('store', 'maintenance', '1');
        $this->assertTrue($this->service->isMaintenanceMode());
    }

    public function testUpsertGroup(): void
    {
        $this->service->setGroup('config', ['key1' => 'val1', 'key2' => 'val2']);
        $this->service->setGroup('config', ['key1' => 'updated', 'key3' => 'val3']);

        $this->assertEquals('updated', $this->service->get('config', 'key1'));
        $this->assertEquals('val3', $this->service->get('config', 'key3'));
    }
}
