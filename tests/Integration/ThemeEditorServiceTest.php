<?php
declare(strict_types=1);

namespace CoreCart\Tests\Integration;

use CoreCart\System\Service\ThemeEditorService;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ThemeEditorServiceTest extends TestCase
{
    private ThemeEditorService $service;
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/corecart_theme_test_' . uniqid();
        $adminDir = $this->testDir . '/admin/View/theme/default/template';
        $catalogDir = $this->testDir . '/catalog/View/theme/default/template';
        $backupDir = $this->testDir . '/storage/backups/theme-editor';

        mkdir($adminDir, 0777, true);
        mkdir($catalogDir, 0777, true);
        mkdir($backupDir . '/admin', 0777, true);
        mkdir($backupDir . '/catalog', 0777, true);

        file_put_contents($adminDir . '/test.html.twig', '<h1>Admin Template</h1>');
        file_put_contents($catalogDir . '/home.html.twig', '<h1>Storefront Template</h1>');

        $loader = new FilesystemLoader([$adminDir, $catalogDir]);
        $twig = new Environment($loader, ['strict_variables' => false]);

        $this->service = new ThemeEditorService($twig, $this->testDir);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->testDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    public function testReadFile(): void
    {
        $content = $this->service->readFile('admin', 'test.html.twig');
        $this->assertEquals('<h1>Admin Template</h1>', $content);
    }

    public function testReadFileInvalidArea(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->readFile('invalid', 'test.html.twig');
    }

    public function testValidateSyntaxValid(): void
    {
        $result = $this->service->validateSyntax('<h1>Hello {{ name }}</h1>');
        $this->assertTrue($result['valid']);
    }

    public function testValidateSyntaxInvalid(): void
    {
        $result = $this->service->validateSyntax('{% if unclosed %}');
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testSaveCreatesBackup(): void
    {
        $this->service->saveFile('admin', 'test.html.twig', '<h1>Updated</h1>');

        $content = $this->service->readFile('admin', 'test.html.twig');
        $this->assertEquals('<h1>Updated</h1>', $content);

        $backups = $this->service->getBackups('admin', 'test.html.twig');
        $this->assertCount(1, $backups);
    }

    public function testSaveRotatesOldBackups(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->service->saveFile('admin', 'test.html.twig', "<h>Version {$i}</h>");
        }

        $backups = $this->service->getBackups('admin', 'test.html.twig');
        $this->assertLessThanOrEqual(20, count($backups));
    }

    public function testRestoreBackup(): void
    {
        $this->service->saveFile('admin', 'test.html.twig', '<h1>First save</h1>');
        $this->service->saveFile('admin', 'test.html.twig', '<h1>Second save</h1>');
        $this->assertEquals('<h1>Second save</h1>', $this->service->readFile('admin', 'test.html.twig'));

        $backups = $this->service->getBackups('admin', 'test.html.twig');
        $this->assertGreaterThanOrEqual(2, count($backups));

        $newestBackup = $backups[0];
        $result = $this->service->restoreBackup('admin', 'test.html.twig', $newestBackup);
        $this->assertTrue($result);
        $this->assertEquals('<h1>First save</h1>', $this->service->readFile('admin', 'test.html.twig'));
    }

    public function testSaveRejectsInvalidSyntax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->saveFile('admin', 'test.html.twig', '{% if broken %}');
    }

    public function testGetAreaDir(): void
    {
        $adminDir = $this->service->getAreaDir('admin');
        $this->assertStringContainsString('admin', $adminDir);

        $catalogDir = $this->service->getAreaDir('catalog');
        $this->assertStringContainsString('catalog', $catalogDir);
    }
}
