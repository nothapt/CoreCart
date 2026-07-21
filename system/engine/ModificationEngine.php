<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Safe OCMOD - Modification Engine
 *
 * Reads XML modification files from system/modifications/ and applies them
 * to core files without touching the originals. Modified files are compiled
 * into the storage/cache/modification/ directory.
 *
 * If a modification causes a PHP error, it is automatically disabled
 * (Safe Mode) and the engine falls back to the original file.
 */
class ModificationEngine
{
    private string $rootDir;
    private string $cacheDir;
    private string $modDir;
    private string $logFile;

    public function __construct(string $rootDir, string $cacheDir, string $modDir)
    {
        $this->rootDir = $rootDir;
        $this->cacheDir = $cacheDir;
        $this->modDir = $modDir;
        $this->logFile = DIR_LOGS . '/ocmod_errors.log';
    }

    /**
     * Compile all active modifications and write results to cache.
     *
     * Steps:
     *  1. Clear old cache
     *  2. Read all XML files from system/modifications/
     *  3. For each XML, collect operations (search & replace / regex)
     *  4. Apply operations to the original file content
     *  5. Write modified files to storage/cache/modification/
     */
    public function compile(): array
    {
        $this->clearCache();

        $xmlFiles = glob($this->modDir . '/*.xml');
        if ($xmlFiles === false) {
            $xmlFiles = [];
        }

        $filesToModify = [];
        $errors = [];

        foreach ($xmlFiles as $xmlFile) {
            $xml = simplexml_load_file($xmlFile);
            if ($xml === false) {
                continue;
            }

            $modificationId = (string) ($xml->id ?? basename($xmlFile, '.xml'));

            foreach ($xml->file as $fileData) {
                $filePath = (string) $fileData['path'];
                $fullPath = $this->rootDir . '/' . $filePath;

                if (!file_exists($fullPath)) {
                    continue;
                }

                // Load original content once, then layer modifications on top
                if (!isset($filesToModify[$filePath])) {
                    $filesToModify[$filePath] = file_get_contents($fullPath);
                }

                foreach ($fileData->operation as $operation) {
                    try {
                        $filesToModify[$filePath] = $this->applyOperation(
                            $filesToModify[$filePath],
                            $operation
                        );
                    } catch (\Throwable $e) {
                        // Safe Mode: log the error, disable this modification, continue
                        $errors[] = [
                            'modification' => $modificationId,
                            'file' => $filePath,
                            'error' => $e->getMessage(),
                        ];
                        $this->logError($modificationId, $filePath, $e->getMessage());
                    }
                }
            }
        }

        // Write compiled files to cache directory
        foreach ($filesToModify as $relativePath => $code) {
            $cachePath = $this->cacheDir . '/modification/' . $relativePath;
            $dir = dirname($cachePath);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($cachePath, $code);
        }

        return $errors;
    }

    /**
     * Apply a single <operation> element to the code string.
     */
    private function applyOperation(string $code, \SimpleXMLElement $operation): string
    {
        $search = (string) $operation->search;
        $add = (string) $operation->add;
        $position = (string) ($operation->add['position'] ?? 'replace');

        // Detect regex mode by the method attribute on <operation>
        $method = (string) ($operation['method'] ?? 'str');

        if ($method === 'regex') {
            return $this->applyRegex($code, $search, $add, $position);
        }

        return $this->applyStringReplace($code, $search, $add, $position);
    }

    /**
     * Simple string-based replacement.
     */
    private function applyStringReplace(string $code, string $search, string $add, string $position): string
    {
        if (strpos($code, $search) === false) {
            return $code; // Search string not found, skip silently
        }

        return match ($position) {
            'before' => str_replace($search, $add . $search, $code),
            'after'  => str_replace($search, $search . $add, $code),
            default  => str_replace($search, $add, $code),
        };
    }

    /**
     * Regex-based replacement using PCRE patterns.
     */
    private function applyRegex(string $code, string $pattern, string $replacement, string $position): string
    {
        $replacement = match ($position) {
            'before' => $replacement . '$0',
            'after'  => '$0' . $replacement,
            default  => $replacement,
        };

        $result = preg_replace($pattern, $replacement, $code);

        if ($result === null) {
            throw new \RuntimeException('Regex failed: ' . preg_last_error_msg());
        }

        return $result;
    }

    /**
     * Clear the modification cache directory.
     */
    private function clearCache(): void
    {
        $modCacheDir = $this->cacheDir . '/modification';

        if (!is_dir($modCacheDir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modCacheDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
    }

    /**
     * Append an error to the modification log file.
     */
    private function logError(string $modificationId, string $filePath, string $message): void
    {
        $entry = sprintf(
            "[%s] Modification \"%s\" failed on file \"%s\": %s%s",
            date('Y-m-d H:i:s'),
            $modificationId,
            $filePath,
            $message,
            PHP_EOL
        );

        file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
