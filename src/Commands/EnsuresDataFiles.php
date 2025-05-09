<?php

namespace Io238\ISOCountries\Commands;

use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * Trait to ensure ISO Countries package data files are present
 */
trait EnsuresDataFiles
{
    /**
     * Ensure required data files exist by copying from vendor if necessary.
     */
    private function ensureDataFilesExist(): void
    {
        // Dynamically determine package root path via reflection
        $reflector   = new ReflectionClass(static::class);
        $packageRoot = dirname($reflector->getFileName(), 3);
        $dataDir     = $packageRoot.'/data';

        // Copy flags
        $this->ensureDirectoryCopy(
            $packageRoot.'/vendor/components/flag-icon-css/flags',
            $dataDir.'/flags',
            'Flag icons '
        );

        // Copy translations: countries, languages, currencies
        foreach (['countries', 'languages', 'currencies'] as $type) {
            $source      = $packageRoot."/vendor/umpirsky/{$type}-list/data";
            $destination = $dataDir."/translations/{$type}";
            $this->ensureDirectoryCopy($source, $destination, ucfirst($type).' translations', 'php');
        }
    }

    /**
     * Copy a directory recursively, filtering by extension if provided.
     * Only copies files that do not exist or have changed.
     */
    private function ensureDirectoryCopy(
        string $source,
        string $destination,
        string $label,
        string $extensionFilter = null
    ): void {
        if (!File::isDirectory($source)) {
            $this->warn("Vendor {$label} directory not found: {$source}");
            return;
        }

        // Ensure destination directory exists
        File::ensureDirectoryExists($destination, 0755);

        // Recursively copy files
        foreach (File::allFiles($source) as $file) {
            if ($extensionFilter && $file->getExtension() !== $extensionFilter) {
                continue;
            }

            $relative   = $file->getRelativePathname();
            $targetPath = "{$destination}/{$relative}";

            File::ensureDirectoryExists(dirname($targetPath), 0755);

            // Copy only if missing or modified
            if (!File::exists($targetPath)
                || File::lastModified($file) !== File::lastModified($targetPath)) {
                File::copy($file->getRealPath(), $targetPath);
                $this->info("Copied {$label} file: {$relative}");
            }
        }
    }
}
