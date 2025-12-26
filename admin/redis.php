<?php
/**
 |--------------------------------------------------------------------------|
 |   https://github.com/Bigjoos/                                            |
 |--------------------------------------------------------------------------|
 |   Licence Info: WTFPL                                                    |
 |--------------------------------------------------------------------------|
 |   Copyright (C) 2010 U-232 V5                                            |
 |--------------------------------------------------------------------------|
 |   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.   |
 |--------------------------------------------------------------------------|
 |   Project Leaders: Mindless, Autotron, whocares, Swizzles.               |
 |--------------------------------------------------------------------------|
 */
if (!defined('IN_INSTALLER09_ADMIN')) {
    $HTMLOUT = '';
    $HTMLOUT .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
        \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
        <html xmlns='http://www.w3.org/1999/xhtml'>
        <head>
        <title>Error!</title>
        </head>
        <body>
    <div style='font-size:33px;color:white;background-color:red;text-align:center;'>Incorrect access<br />You cannot access this file directly.</div>
    </body></html>";
    echo $HTMLOUT;
    exit();
}
require_once (INCL_DIR . 'user_functions.php');
require_once (CLASS_DIR . 'class_check.php');
class_check(UC_MAX);
$lang = array_merge($lang, load_language('ad_mysql_overview'));

$HTMLOUT = '';
$HTMLOUT .= "<h2>Redis Cache</h2>";

$redis_ok = extension_loaded('redis');
if (!$redis_ok) {
    $HTMLOUT .= "<div class='notreadable'>PHP Redis extension not loaded.</div>";
} else {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        //$redis->auth('your_redis_password'); // if needed
        $dbsize = $redis->dbSize();
        $info = $redis->info();
        $used_memory = isset($info['used_memory_human']) ? $info['used_memory_human'] : (isset($info['used_memory']) ? $info['used_memory'] : 'n/a');
        $HTMLOUT .= "<table class='torrenttable' border='1' cellpadding='4px'>";
        $HTMLOUT .= "<tr><td class='colhead'>Keys</td><td>" . (int)$dbsize . "</td></tr>";
        $HTMLOUT .= "<tr><td class='colhead'>Used Memory</td><td>" . htmlsafechars($used_memory) . "</td></tr>";
        $HTMLOUT .= "</table>";
        $HTMLOUT .= "<br/><a class='btn' href='" . $INSTALLER09['baseurl'] . "/staffpanel.php?tool=redis&action=flushdb' onclick='return confirm(\"Flush entire Redis DB?\");'>Flush DB</a>";
        if (isset($_GET['action']) && $_GET['action'] === 'flushdb') {
            $redis->flushDB();
            $HTMLOUT .= "<div class='readable'>Redis database flushed.</div>";
        }
    } catch (Exception $e) {
        $HTMLOUT .= "<div class='notreadable'>Redis error: " . htmlsafechars($e->getMessage()) . "</div>";
    }
}

echo stdhead('Redis') . $HTMLOUT . stdfoot();
