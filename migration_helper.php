#!/usr/bin/env php
<?php
/**
 * PHP 8.2 Migration Helper Script
 * 
 * This script helps automate common migration patterns for updating
 * the U-232 V5 codebase to PHP 8.2 standards.
 * 
 * Usage: php migration_helper.php [directory]
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line');
}

$directory = $argv[1] ?? __DIR__;

if (!is_dir($directory)) {
    die("Error: Directory '$directory' does not exist\n");
}

echo "PHP 8.2 Migration Helper\n";
echo "========================\n\n";
echo "Scanning directory: $directory\n\n";

// Pattern replacements
$patterns = [
    // Operators
    [
        'name' => 'Replace AND with &&',
        'search' => '/\s+AND\s+/',
        'replace' => ' && ',
        'regex' => true
    ],
    [
        'name' => 'Replace OR with ||',
        'search' => '/\s+OR\s+/',
        'replace' => ' || ',
        'regex' => true
    ],
    
    // Array syntax (simple cases only)
    [
        'name' => 'Replace array() with []',
        'search' => '/array\(\s*\)/',
        'replace' => '[]',
        'regex' => true
    ],
    
    // Remove sqlerr calls (since sql_query now throws exceptions)
    [
        'name' => 'Remove or sqlerr() patterns',
        'search' => '/\s+or\s+sqlerr\([^)]+\);/',
        'replace' => ';',
        'regex' => true,
        'review' => true
    ],
    
    // Strict comparisons
    [
        'name' => 'Replace == with === for numeric comparisons',
        'search' => '/\s+==\s+0(?!\d)/',
        'replace' => ' === 0',
        'regex' => true
    ],
];

// Statistics
$stats = [
    'files_scanned' => 0,
    'files_modified' => 0,
    'total_changes' => 0
];

// Find all PHP files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory)
);

$phpFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

echo "Found " . count($phpFiles) . " PHP files\n\n";

// Process each file
foreach ($phpFiles as $filePath) {
    $stats['files_scanned']++;
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $fileChanges = 0;
    
    // Apply each pattern
    foreach ($patterns as $pattern) {
        $before = $content;
        
        if ($pattern['regex']) {
            $content = preg_replace($pattern['search'], $pattern['replace'], $content, -1, $count);
        } else {
            $count = 0;
            $content = str_replace($pattern['search'], $pattern['replace'], $content, $count);
        }
        
        if ($count > 0) {
            $fileChanges += $count;
            echo "  {$pattern['name']}: $count replacement(s)\n";
            
            if (isset($pattern['review']) && $pattern['review']) {
                echo "    ⚠ REVIEW RECOMMENDED: Manual verification suggested\n";
            }
        }
    }
    
    // Save if changes were made
    if ($content !== $originalContent) {
        // Create backup
        $backupPath = $filePath . '.backup';
        copy($filePath, $backupPath);
        
        // Save modified content
        file_put_contents($filePath, $content);
        
        $stats['files_modified']++;
        $stats['total_changes'] += $fileChanges;
        
        $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $filePath);
        echo "✓ Modified: $relativePath ($fileChanges changes)\n";
    }
}

// Summary
echo "\n";
echo "Migration Summary\n";
echo "=================\n";
echo "Files scanned:   {$stats['files_scanned']}\n";
echo "Files modified:  {$stats['files_modified']}\n";
echo "Total changes:   {$stats['total_changes']}\n\n";

if ($stats['files_modified'] > 0) {
    echo "✓ Migration patterns applied successfully!\n";
    echo "  Backup files created with .backup extension\n";
    echo "  Please review changes and test thoroughly\n\n";
    echo "⚠ IMPORTANT:\n";
    echo "  - Test in development environment first\n";
    echo "  - Review all changes manually\n";
    echo "  - Pay special attention to error handling changes\n";
    echo "  - Remove .backup files once verified\n";
} else {
    echo "No changes needed - files already appear to be updated\n";
}

echo "\n";
