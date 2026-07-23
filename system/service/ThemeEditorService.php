<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use Twig\Environment;

class ThemeEditorService
{
    private Environment $twig;
    private string $baseDir;
    private int $maxBackups = 20;

    public function __construct(Environment $twig, string $baseDir = '')
    {
        $this->twig = $twig;
        $this->baseDir = $baseDir !== '' ? $baseDir : dirname(__DIR__, 2);
    }

    public function getTemplatePath(string $area, string $file): string
    {
        $this->validateArea($area);
        $this->validateFile($file);

        $root = $this->getAreaDir($area);
        $rootReal = realpath($root);
        if ($rootReal === false) {
            throw new \InvalidArgumentException("Theme directory not found for area: {$area}");
        }

        $candidate = realpath($rootReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
        if (
            $candidate === false
            || !is_file($candidate)
            || !str_starts_with($candidate, $rootReal . DIRECTORY_SEPARATOR)
        ) {
            throw new \InvalidArgumentException("Template not found: {$file}");
        }

        return $candidate;
    }

    public function readFile(string $area, string $file): string
    {
        $path = $this->getTemplatePath($area, $file);
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Unable to read template: {$file}");
        }
        return $content;
    }

    public function validateSyntax(string $content): array
    {
        try {
            $source = new \Twig\Source($content, 'theme_editor');
            $tokenStream = $this->twig->tokenize($source);
            $this->twig->parse($tokenStream);
            return ['valid' => true];
        } catch (\Twig\Error\SyntaxError $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    public function saveFile(string $area, string $file, string $content): bool
    {
        $validation = $this->validateSyntax($content);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException('Syntax error: ' . $validation['error']);
        }

        $path = $this->getTemplatePath($area, $file);

        $this->createBackup($area, $file, $path);

        $tmp = $path . '.tmp.' . getmypid();
        $written = file_put_contents($tmp, $content, LOCK_EX);
        if ($written === false) {
            @unlink($tmp);
            throw new \RuntimeException('Unable to write template');
        }

        if (!rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('Unable to replace template');
        }

        $this->rotateBackups($area, $file);

        return true;
    }

    public function getBackups(string $area, string $file): array
    {
        $this->validateArea($area);
        $this->validateFile($file);

        $backupDir = $this->backupDir($area, $file);
        if (!is_dir($backupDir)) {
            return [];
        }

        $pattern = $backupDir . DIRECTORY_SEPARATOR . '*.bak';
        $backups = glob($pattern) ?: [];
        rsort($backups, SORT_STRING);

        return array_map(function (string $path) {
            return basename($path);
        }, $backups);
    }

    public function restoreBackup(string $area, string $file, string $backupFile): bool
    {
        $path = $this->getTemplatePath($area, $file);

        if (
            $backupFile === ''
            || str_contains($backupFile, "\0")
            || str_contains($backupFile, '..')
            || !str_ends_with($backupFile, '.bak')
        ) {
            throw new \InvalidArgumentException('Invalid backup filename');
        }

        $backupPath = $this->backupDir($area, $file) . DIRECTORY_SEPARATOR . $backupFile;
        if (!is_file($backupPath)) {
            throw new \InvalidArgumentException("Backup not found: {$backupFile}");
        }

        $this->createBackup($area, $file, $path);

        if (!copy($backupPath, $path)) {
            throw new \RuntimeException('Unable to restore from backup');
        }

        return true;
    }

    public function getAreaDir(string $area): string
    {
        $this->validateArea($area);
        return $this->baseDir
            . DIRECTORY_SEPARATOR . $area
            . DIRECTORY_SEPARATOR . 'View'
            . DIRECTORY_SEPARATOR . 'theme'
            . DIRECTORY_SEPARATOR . 'default'
            . DIRECTORY_SEPARATOR . 'template';
    }

    public function setMaxBackups(int $max): void
    {
        $this->maxBackups = max(1, $max);
    }

    private function validateArea(string $area): void
    {
        if (!in_array($area, ['catalog', 'admin'], true)) {
            throw new \InvalidArgumentException("Invalid area: {$area}. Must be 'catalog' or 'admin'.");
        }
    }

    private function validateFile(string $file): void
    {
        if (
            $file === ''
            || str_contains($file, "\0")
            || str_contains($file, '..')
            || !str_ends_with(strtolower($file), '.twig')
        ) {
            throw new \InvalidArgumentException("Invalid template file: {$file}");
        }
    }

    private function backupDir(string $area, string $file): string
    {
        $dir = dirname($file);
        $base = $this->baseDir
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'backups'
            . DIRECTORY_SEPARATOR . 'theme-editor'
            . DIRECTORY_SEPARATOR . $area;

        if ($dir !== '.' && $dir !== '' && $dir !== DIRECTORY_SEPARATOR) {
            $base .= DIRECTORY_SEPARATOR . $dir;
        }

        return $this->normalizePath($base);
    }

    private function normalizePath(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = explode(DIRECTORY_SEPARATOR, $normalized);
        $result = [];
        foreach ($parts as $part) {
            if ($part === '.' || $part === '') {
                continue;
            }
            if ($part === '..') {
                array_pop($result);
            } else {
                $result[] = $part;
            }
        }
        $prefix = '';
        if (isset($result[0]) && preg_match('/^[A-Za-z]:$/', $result[0])) {
            $prefix = array_shift($result) . DIRECTORY_SEPARATOR;
        }
        return $prefix . implode(DIRECTORY_SEPARATOR, $result);
    }

    private function createBackup(string $area, string $file, string $path): void
    {
        $dir = $this->backupDir($area, $file);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create backup directory');
        }

        $backupPath = $dir
            . DIRECTORY_SEPARATOR . basename($file)
            . '.' . date('Ymd-His') . '-' . substr((string) microtime(true), -6)
            . '.bak';

        if (!copy($path, $backupPath)) {
            throw new \RuntimeException('Unable to create backup');
        }
    }

    private function rotateBackups(string $area, string $file): void
    {
        $dir = $this->backupDir($area, $file);
        if (!is_dir($dir)) {
            return;
        }

        $basename = basename($file);
        $allFiles = glob($dir . DIRECTORY_SEPARATOR . '*.bak') ?: [];
        $backups = array_filter($allFiles, function (string $path) use ($basename) {
            return str_starts_with(basename($path), $basename . '.');
        });
        $backups = array_values($backups);
        rsort($backups, SORT_STRING);

        if (count($backups) <= $this->maxBackups) {
            return;
        }

        $toDelete = array_slice($backups, $this->maxBackups);
        foreach ($toDelete as $old) {
            @unlink($old);
        }
    }
}
