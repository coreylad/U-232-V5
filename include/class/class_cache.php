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
  _   _   _   _   _     _   _   _   _   _   _     _   _   _   _
 / \ / \ / \ / \ / \   / \ / \ / \ / \ / \ / \   / \ / \ / \ / \
( U | - | 2 | 3 | 2 )-( S | o | u | r | c | e )-( C | o | d | e )
 \_/ \_/ \_/ \_/ \_/   \_/ \_/ \_/ \_/ \_/ \_/   \_/ \_/ \_/ \_/
 */
/*
define('FALLBACK_CACHE_DIR', '/var/www/cache/fallback/');
$md5 = md5($key);
$file = FALLBACK_CACHE_DIR . substr($md5, 0, 1) . '/' . substr($md5, 0, 2) . '/' . substr($md5, 0, 3) . '/' . $file;
//== Read the cache
if ($value === null) {
    if (file_exists($file)) {
        $result = unserialize(file_get_contents($file));
        if (!$expires || $result['time'] <= $time) {
            @unlink($file);
        } else {
            $value = $result['data'];
        }
    }
}
//== Writing the cache
else {
    if (!file_exists(dirname($file))) {
        mkdir(dirname($file), 0700, true);
    }
    file_put_contents($file, serialize(array('data' => $value, 'time' => $expires)));
}*/

if (!extension_loaded('redis')) {
    die('Redis Extension not loaded.');
}
class CACHE {
    private $redis;
    public $CacheHits = array();
    public $MemcacheDBArray = array();
    public $MemcacheDBKey = '';
    protected $InTransaction = false;
    public $Time = 0;
    protected $Page = array();
    protected $Row = 1;
    protected $Part = 0;
    public static $connected = false;
    private static $link = NULL;
    
    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
        // Optional: set password if Redis requires authentication
        // $this->redis->auth('your_redis_password');
    }
    //---------- Caching functions ----------//
    // Wrapper for Redis::set, with default duration of 30 days
    public function cache_value($Key, $Value, $Duration = 2592000) {
        $StartTime = microtime(true);
        
        if (empty($Key)) {
            trigger_error("Cache insert failed for empty key");
        }
        
        $serialized = serialize($Value);
        if ($Duration == 0) {
            $result = $this->redis->set($Key, $serialized);
        } else {
            $result = $this->redis->setex($Key, $Duration, $serialized);
        }
        
        if (!$result) {
            trigger_error("Cache insert failed for key $Key", E_USER_ERROR);
        }
        $this->Time += (microtime(true) - $StartTime) * 1000;
    }
    public function add_value($Key, $Value, $Duration = 2592000) {
        $StartTime = microtime(true);
        
        if (empty($Key)) {
            trigger_error("Cache insert failed for empty key");
        }
        
        // Redis setnx (set if not exists)
        $serialized = serialize($Value);
        $add = $this->redis->setnx($Key, $serialized);
        
        if ($add && $Duration > 0) {
            $this->redis->expire($Key, $Duration);
        }
        
        $this->Time += (microtime(true) - $StartTime) * 1000;
        return $add;
    }
    public function get_value($Key, $NoCache = false) {
        $StartTime = microtime(true);
        if (empty($Key)) {
            trigger_error("Cache retrieval failed for empty key");
        }
        $value = $this->redis->get($Key);
        $Return = ($value !== false) ? unserialize($value) : false;
        $this->Time += (microtime(true) - $StartTime) * 1000;
        return $Return;
    }
    public function replace_value($Key, $Value, $Duration = 2592000) {
        $StartTime = microtime(true);
        
        // Only replace if key exists
        if ($this->redis->exists($Key)) {
            $serialized = serialize($Value);
            if ($Duration == 0) {
                $this->redis->set($Key, $serialized);
            } else {
                $this->redis->setex($Key, $Duration, $serialized);
            }
        }
        
        $this->Time += (microtime(true) - $StartTime) * 1000;
    }
    // Wrapper for Redis::del
    public function delete_value($Key) {
        $StartTime = microtime(true);
        if (empty($Key)) {
            trigger_error("Cache retrieval failed for empty key");
        }
        $this->redis->del($Key);
        $this->Time += (microtime(true) - $StartTime) * 1000;
    }
    // Return cache stats compatible with legacy Memcached expectations
    public function getStats() {
        $info = $this->redis->info();
        $hits = isset($info['keyspace_hits']) ? (int)$info['keyspace_hits'] : 0;
        $misses = isset($info['keyspace_misses']) ? (int)$info['keyspace_misses'] : 0;
        $cmd_get = $hits + $misses;
        $keys = 0;
        // Prefer db size for current items
        try {
            $keys = $this->redis->dbSize();
        } catch (Throwable $e) {
            $keys = 0;
        }
        $stats = array(
            'cmd_get' => $cmd_get,
            'get_hits' => $hits,
            'curr_items' => $keys,
        );
        // Provide both memcache-style server keys to preserve template access
        return array(
            '127.0.0.1:11211' => $stats,
            '127.0.0.1:6379' => $stats,
        );
    }
    //---------- Redis cache transaction functions ----------//
    public function begin_transaction($Key) {
        $Value = $this->get_value($Key);
        if (!is_array($Value)) {
            $this->InTransaction = false;
            $this->MemcacheDBKey = array();
            $this->MemcacheDBKey = '';
            return false;
        }
        $this->MemcacheDBArray = $Value;
        $this->MemcacheDBKey = $Key;
        $this->InTransaction = true;
        return true;
    }
    public function cancel_transaction() {
        $this->InTransaction = false;
        $this->MemcacheDBKey = array();
        $this->MemcacheDBKey = '';
    }
    public function commit_transaction($Time = 2592000) {
        if (!$this->InTransaction) {
            return false;
        }
        $this->cache_value($this->MemcacheDBKey, $this->MemcacheDBArray, $Time);
        $this->InTransaction = false;
    }
    // Updates multiple rows in an array
    public function update_transaction($Rows, $Values) {
        if (!$this->InTransaction) {
            return false;
        }
        $Array = $this->MemcacheDBArray;
        if (is_array($Rows)) {
            $i = 0;
            $Keys = $Rows[0];
            $Property = $Rows[1];
            foreach ($Keys as $Row) {
                $Array[$Row][$Property] = $Values[$i];
                $i++;
            }
        } else {
            $Array[$Rows] = $Values;
        }
        $this->MemcacheDBArray = $Array;
    }
    // Updates multiple values in a single row in an array
    // $Values must be an associative array with key:value pairs like in the array we're updating
    public function update_row($Row, $Values) {
        if (!$this->InTransaction) {
            return false;
        }
        if ($Row === false) {
            $UpdateArray = $this->MemcacheDBArray;
        } else {
            $UpdateArray = $this->MemcacheDBArray[$Row];
        }
        foreach ($Values as $Key => $Value) {
            if (!array_key_exists($Key, $UpdateArray)) {
                trigger_error('Bad transaction key (' . $Key . ') for cache ' . $this->MemcacheDBKey);
            }
            if ($Value === '+1') {
                if (!is_number($UpdateArray[$Key])) {
                    trigger_error('Tried to increment non-number (' . $Key . ') for cache ' . $this->MemcacheDBKey);
                }
                ++$UpdateArray[$Key]; // Increment value

            } elseif ($Value === '-1') {
                if (!is_number($UpdateArray[$Key])) {
                    trigger_error('Tried to decrement non-number (' . $Key . ') for cache ' . $this->MemcacheDBKey);
                }
                --$UpdateArray[$Key]; // Decrement value

            } else {
                $UpdateArray[$Key] = $Value; // Otherwise, just alter value

            }
        }
        if ($Row === false) {
            $this->MemcacheDBArray = $UpdateArray;
        } else {
            $this->MemcacheDBArray[$Row] = $UpdateArray;
        }
    }
    //---------- Redis cache flushing ----------//
    public function flush_all() {
        /**
         * Flush all data from the current Redis database
         * Used for development mode to force cache refresh
         */
        return $this->redis->flushDB();
    }
    public function flush_keys_pattern($pattern = '*') {
        /**
         * Delete all keys matching a pattern
         * Example: flush_keys_pattern('user*') - deletes all user-related cache
         */
        $keys = $this->redis->keys($pattern);
        if (!empty($keys)) {
            return $this->redis->delete($keys);
        }
        return 0;
    }
    //---------- Redis native stats ----------//
    public function stats() {
        $info = $this->redis->info();
        $hits = isset($info['keyspace_hits']) ? (int)$info['keyspace_hits'] : 0;
        $misses = isset($info['keyspace_misses']) ? (int)$info['keyspace_misses'] : 0;
        $cmd_get = $hits + $misses;
        $keys = 0;
        try {
            $keys = $this->redis->dbSize();
        } catch (Throwable $e) {
            $keys = 0;
        }
        return array(
            'cmd_get' => $cmd_get,
            'hits' => $hits,
            'misses' => $misses,
            'curr_items' => $keys,
        );
    }
}//end class
?>
