<?php
/**
 * Promote a user to Sysop (UC_SYSOP = 6) and refresh staff caches.
 * Usage (CLI or browser):
 *   php tools/promote_sysop.php 1
 *   or hit /tools/promote_sysop.php?user_id=1
 */

// Bootstrap - let config.php define constants
require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'user_functions.php';

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

// Check if user exists first
$check = sql_query("SELECT id, username, class FROM users WHERE id = " . sqlesc($userId) . " LIMIT 1");
if (!$check || mysqli_num_rows($check) === 0) {
    die("User {$userId} not found.\n");
}
$userData = mysqli_fetch_assoc($check);
echo "Found user: {$userData['username']} (current class: {$userData['class']})\n";

// Update to sysop
$res = sql_query("UPDATE users SET class = " . UC_SYSOP . " WHERE id = " . sqlesc($userId));
if (!$res) {
    die("Update query failed: " . mysqli_error($GLOBALS['___mysqli_ston']) . "\n");
}
$affected = mysqli_affected_rows($GLOBALS['___mysqli_ston']);
if ($affected === 0) {
    echo "User already has class {$userData['class']} (no change needed).\n";
} else {
    echo "Updated {$affected} row(s).\n";
}

// Refresh staff caches
if (function_exists('write_staffs')) {
    echo "Refreshing staff caches...\n";
    write_staffs();
    echo "Staff caches refreshed.\n";
}

echo "Done! User {$userId} is now Sysop (class 6).\n";
?>
