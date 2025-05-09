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

        // 使用新方法查找 vendor 目录
        $vendorPath = $this->findVendorPath($packageRoot);
        $this->info("Using vendor path: {$vendorPath}");

        // Copy flags
        $this->ensureDirectoryCopy(
            $vendorPath.'/components/flag-icon-css/flags',
            $dataDir.'/flags',
            'Flag icons '
        );

        // 复制翻译文件
        $typeMap = [
            'countries' => ['countries-list', 'country-list'],
            'languages' => ['languages-list', 'language-list'],
            'currencies' => ['currencies-list', 'currency-list']
        ];

        foreach ($typeMap as $type => $possibleDirs) {
            $destination = $dataDir."/translations/{$type}";
            $copied = false;

            foreach ($possibleDirs as $dir) {
                $source = $vendorPath."/umpirsky/{$dir}/data";
                if (is_dir($source)) {
                    $this->ensureDirectoryCopy($source, $destination, ucfirst($type).' translations', 'php');
                    $copied = true;
                    break;
                }
            }

            if (!$copied) {
                $this->warn("Could not find translation directory for {$type}. Tried: " . implode(', ', array_map(fn($dir) => $vendorPath."/umpirsky/{$dir}/data", $possibleDirs)));
            }
        }
    }

    /**
     * 智能查找 vendor 目录路径
     * 考虑多种安装场景：独立安装、项目依赖、嵌套依赖等
     */
    private function findVendorPath(string $packageRoot): string
    {
        // 可能的路径列表，按优先级排序
        $possiblePaths = [
            // 标准项目结构下的 vendor 路径
            $packageRoot . '/vendor',

            // 如果当前包是安装在项目的 vendor 目录下
            dirname(dirname($packageRoot)) . '/vendor',

            // 如果当前包是安装在项目的 vendor/vendor_name 目录下
            dirname(dirname(dirname($packageRoot))) . '/vendor'
        ];

        // 添加基于 Composer 类加载器的路径检测
        if (class_exists('Composer\Autoload\ClassLoader')) {
            // 尝试从 Composer 类加载器获取项目根目录
            $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
            $composerVendorDir = dirname(dirname($reflection->getFileName()));
            if (!in_array($composerVendorDir, $possiblePaths)) {
                array_unshift($possiblePaths, $composerVendorDir);
            }
        }

        // 检查路径是否存在并包含必要的依赖
        foreach ($possiblePaths as $path) {
            $this->line("Checking vendor path: {$path}");

            $flagsPath = $path . '/components/flag-icon-css/flags';
            $hasUmpirsky = is_dir($path . '/umpirsky');

            $hasFlagsDir = is_dir($flagsPath);

            $this->line("  Flags directory exists: " . ($hasFlagsDir ? 'Yes' : 'No'));
            $this->line("  Umpirsky directory exists: " . ($hasUmpirsky ? 'Yes' : 'No'));

            if (is_dir($path) && ($hasFlagsDir || $hasUmpirsky)) {
                $this->info("Found valid vendor directory: {$path}");
                return $path;
            }
        }

        // 如果找不到合适的路径，返回包根目录下的 vendor 作为默认值
        $this->warn("No valid vendor directory found, using default: {$packageRoot}/vendor");
        return $packageRoot . '/vendor';
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
