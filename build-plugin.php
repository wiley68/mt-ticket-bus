<?php

/**
 * Build script for MT Ticket Bus plugin.
 *
 * Usage (from plugin root):
 *   php build-plugin.php
 *
 * - Creates a ./build/mt-ticket-bus/ directory with a clean copy of the plugin
 * - Excludes: vendor/, app/, build/, .git/, node_modules/, IDE files, tests
 * - Runs composer install --no-dev --prefer-dist inside the build copy (if composer is available)
 * - Creates build/mt-ticket-bus.zip ready for distribution
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$rootDir   = __DIR__;
$buildDir  = $rootDir . DIRECTORY_SEPARATOR . 'build';
$slug      = 'mt-ticket-bus';
$targetDir = $buildDir . DIRECTORY_SEPARATOR . $slug;
$zipFile   = $buildDir . DIRECTORY_SEPARATOR . $slug . '.zip';

echo "Building {$slug} from {$rootDir}\n";

// Ensure build directory exists
if (!is_dir($buildDir)) {
    if (!mkdir($buildDir, 0775, true) && !is_dir($buildDir)) {
        fwrite(STDERR, "Failed to create build directory: {$buildDir}\n");
        exit(1);
    }
}

// Clean previous build
if (is_dir($targetDir)) {
    rrmdir($targetDir);
}
if (file_exists($zipFile)) {
    unlink($zipFile);
}

// Copy plugin files with exclusions
echo "Copying plugin files (excluding vendor/, app/, build/, .git/, node_modules/, IDE files, tests)...\n";
copyDirFiltered(
    $rootDir,
    $targetDir,
    [
        '.',
        '..',
        '.git',
        '.gitignore',
        'node_modules',
        'vendor',
        'build',
        'app',
        '.idea',
        '.vscode',
        'tests',
        'build-plugin.php',
    ]
);

// Run composer install in build copy, if possible
$composer = findComposer();
if ($composer !== null) {
    echo "Running composer install --no-dev --prefer-dist in build copy...\n";
    $cmd = escapeshellcmd($composer) . ' install --no-dev --prefer-dist --no-interaction --no-progress';
    $exitCode = runInDir($targetDir, $cmd);
    if ($exitCode !== 0) {
        fwrite(STDERR, "WARNING: composer install failed with exit code {$exitCode}. Vendor dependencies may be missing.\n");
    } else {
        echo "Composer install completed.\n";
        echo "Cleaning vendor tests/docs (optional)...\n";
        cleanupVendor($targetDir . DIRECTORY_SEPARATOR . 'vendor');
    }
} else {
    echo "WARNING: composer not found in PATH. Skipping vendor install. The build will not contain required PHP dependencies.\n";
}

// Create zip archive
echo "Creating zip package {$zipFile}...\n";
if (!createZip($targetDir, $zipFile)) {
    fwrite(STDERR, "Failed to create zip archive: {$zipFile}\n");
    exit(1);
}

echo "Build complete:\n";
echo " - Directory: {$targetDir}\n";
echo " - Zip file:  {$zipFile}\n";

exit(0);

/**
 * Recursively remove directory.
 *
 * @param string $dir
 * @return void
 */
function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) {
            rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

/**
 * Copy directory with exclusions.
 *
 * @param string   $src
 * @param string   $dst
 * @param string[] $excludeNames
 * @return void
 */
function copyDirFiltered(string $src, string $dst, array $excludeNames = []): void
{
    if (!is_dir($src)) {
        return;
    }
    if (!is_dir($dst) && !mkdir($dst, 0775, true) && !is_dir($dst)) {
        throw new RuntimeException("Failed to create directory: {$dst}");
    }

    $items = scandir($src);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if (in_array($item, $excludeNames, true)) {
            continue;
        }
        $srcPath = $src . DIRECTORY_SEPARATOR . $item;
        $dstPath = $dst . DIRECTORY_SEPARATOR . $item;

        if (is_dir($srcPath) && !is_link($srcPath)) {
            copyDirFiltered($srcPath, $dstPath, $excludeNames);
        } else {
            copyFile($srcPath, $dstPath);
        }
    }
}

/**
 * Copy a single file (with directory creation).
 *
 * @param string $src
 * @param string $dst
 * @return void
 */
function copyFile(string $src, string $dst): void
{
    $dir = dirname($dst);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("Failed to create directory: {$dir}");
    }
    if (!copy($src, $dst)) {
        throw new RuntimeException("Failed to copy file: {$src} -> {$dst}");
    }
}

/**
 * Find composer executable.
 *
 * @return string|null
 */
function findComposer(): ?string
{
    // Respect COMPOSER_BINARY if defined
    if (getenv('COMPOSER_BINARY')) {
        return (string) getenv('COMPOSER_BINARY');
    }

    // Try simple "composer"
    $which = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'which';
    $output = [];
    @exec($which . ' composer', $output, $code);
    if ($code === 0 && !empty($output)) {
        return trim($output[0]);
    }

    return null;
}

/**
 * Run a shell command in a specific directory.
 *
 * @param string $dir
 * @param string $cmd
 * @return int exit code
 */
function runInDir(string $dir, string $cmd): int
{
    $current = getcwd();
    chdir($dir);
    passthru($cmd, $code);
    chdir($current !== false ? $current : $dir);
    return (int) $code;
}

/**
 * Remove common test/docs directories from vendor.
 *
 * @param string $vendorDir
 * @return void
 */
function cleanupVendor(string $vendorDir): void
{
    if (!is_dir($vendorDir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($vendorDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    $removeNames = ['tests', 'test', 'Tests', 'docs', 'doc'];

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if ($file->isDir() && in_array($file->getFilename(), $removeNames, true)) {
            rrmdir($file->getPathname());
        }
    }
}

/**
 * Create ZIP archive from directory.
 *
 * @param string $sourceDir
 * @param string $zipPath
 * @return bool
 */
function createZip(string $sourceDir, string $zipPath): bool
{
    if (!class_exists('ZipArchive')) {
        fwrite(STDERR, "ZipArchive is not available in this PHP build.\n");
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
    $len = strlen($sourceDir) + 1;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        $local = substr($path, $len);

        if ($file->isDir()) {
            $zip->addEmptyDir($local);
        } else {
            $zip->addFile($path, $local);
        }
    }

    $zip->close();
    return true;
}
