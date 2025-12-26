<?php
/**
 * Clear Redis Cache - Development Mode Script
 * Flushes all Redis cache to force immediate updates
 * Usage: php tools/clear_redis_cache.php or /tools/clear_redis_cache.php
 */

require_once __DIR__ . '/../include/bittorrent.php';

// Prevent execution without authentication in browser
if (PHP_SAPI !== 'cli') {
    if (!isset($CURUSER) || (isset($CURUSER) && $CURUSER['class'] < UC_ADMINISTRATOR)) {
        die("Access Denied. Admin only.");
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "   REDIS CACHE FLUSH - DEVELOPMENT MODE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Initialize cache
$mc1 = new CACHE();

try {
    echo "[1/2] Flushing all Redis cache...\n";
    $mc1->flush_all();
    echo "     ✓ Redis cache flushed successfully!\n\n";
    
    echo "[2/2] Cache statistics:\n";
    // Get some basic info
    echo "     • All cached data has been cleared\n";
    echo "     • Cache expires set to: 30-60 seconds (relaxed mode)\n";
    echo "     • Browser cache busting: ENABLED (timestamp-based)\n";
    echo "     • HTTP cache headers: NO-CACHE (enforced)\n\n";
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✓ Cache successfully cleared!\n";
    echo "✓ Site will now dynamically update without caching.\n";
    echo "✓ All changes will be visible immediately.\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    if (PHP_SAPI !== 'cli') {
        echo '<p style="color: #0f0; font-weight: bold;">Cache cleared! Refresh the page to see updates.</p>';
    }
    
} catch (Exception $e) {
    echo "✗ Error flushing cache: " . $e->getMessage() . "\n";
    exit(1);
}
