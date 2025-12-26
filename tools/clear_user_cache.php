<?php
/**
 * Clear Redis cache for a specific user to force session refresh.
 * Usage:
 *   php tools/clear_user_cache.php 1
 *   or hit /tools/clear_user_cache.php?user_id=1
 */

require_once __DIR__ . '/../include/bittorrent.php';

dbconn(false);

$userId = 1;
if (PHP_SAPI === 'cli' && isset($argv[1])) {
    $userId = (int) $argv[1];
} elseif (isset($_GET['user_id'])) {
    $userId = (int) $_GET['user_id'];
}

if ($userId < 1) {
    die("Invalid user_id.\n");
}

// Check user exists and show current class
$check = sql_query("SELECT id, username, class FROM users WHERE id = " . sqlesc($userId) . " LIMIT 1");
if (!$check || mysqli_num_rows($check) === 0) {
    die("User {$userId} not found.\n");
}
$userData = mysqli_fetch_assoc($check);

// Map class to name
$classNames = [
    0 => 'User',
    1 => 'Power User',
    2 => 'VIP',
    3 => 'Uploader',
    4 => 'Moderator',
    5 => 'Administrator',
    6 => 'Sysop'
];
$className = $classNames[$userData['class']] ?? 'Unknown';

echo "User: {$userData['username']} (ID: {$userData['id']})\n";
echo "Current class in DB: {$userData['class']} ({$className})\n\n";

// Clear Redis cache keys
$mc1 = new CACHE();
$keys = [
    'MyUser_' . $userId,
    'user' . $userId,
    'userstats_' . $userId
];

echo "Clearing Redis cache keys:\n";
foreach ($keys as $key) {
    $result = $mc1->delete_value($key);
    echo "  - {$key}: " . ($result ? "cleared" : "not found or error") . "\n";
}

echo "\nCache cleared! Please log out and log back in to see the updated class.\n";
