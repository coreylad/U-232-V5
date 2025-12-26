# Cache Relaxation - Development Mode

## Overview

Your U-232 V5 tracker has been configured for **relaxed caching** to allow dynamic template updates without the aggressive 30-day cache that was previously enabled.

## Changes Made

### 1. Redis Cache TTL Reductions

**Before (Aggressive Caching):**
- User Cache: 30 days (2,592,000 seconds)
- Current User Cache: 30 days (2,592,000 seconds)
- Torrent Details: 30 days
- Shoutbox: 1 day

**After (Development Mode):**
- User Cache: 60 seconds
- Current User Cache: 60 seconds
- Torrent Details: 5 minutes (300 seconds)
- Announcements: 30 seconds
- Shoutbox: 5 minutes (300 seconds)
- Comments: 60 seconds
- Most other data: 30-60 seconds

### 2. HTTP Cache-Busting Headers

Added to all template responses:
```
Cache-Control: no-cache, no-store, must-revalidate, max-age=0, public
Pragma: no-cache
Expires: -1
X-UA-Compatible: IE=edge
```

**Result:** Browser & proxy servers will NOT cache pages

### 3. URL Cache Busting

CSS and JavaScript now include timestamp-based cache busting:
```
Before: /templates/1/css/style.css
After:  /templates/1/css/style.css?1703270400

Timestamp changes on every page load = browser always fetches fresh files
```

**Files Modified:**
- `/templates/1/template.php`
- `/templates/2/template.php`

### 4. Apache .htaccess Rules

Added headers to enforce no-caching at the web server level:
```apache
<IfModule mod_headers.c>
    Header set Cache-Control "no-cache, no-store, must-revalidate, max-age=0, public"
    Header set Pragma "no-cache"
    Header set Expires "-1"
</IfModule>
```

### 5. New Cache Management Tools

**Location:** `/tools/clear_redis_cache.php`

#### Use Cases:

1. **Flush all cache immediately:**
   - CLI: `php /tools/clear_redis_cache.php`
   - Browser: `https://betaups.site/tools/clear_redis_cache.php` (Admin only)

2. **Flush specific pattern:**
   - Can be extended to flush only user caches, announcement caches, etc.

#### Added Cache Methods:
- `flush_all()` - Clear entire Redis database
- `flush_keys_pattern($pattern)` - Clear specific cache keys

## Impact

| Aspect | Before | After |
|--------|--------|-------|
| User Cache Duration | 30 days | 60 seconds |
| Template Load | Cached | Fresh every time |
| CSS/JS Updates | Cached | Fresh every page load |
| Stylesheet Changes | Visible in 30 days | Visible immediately |
| Staff Panel Updates | Cached 30 days | Visible in 60 seconds |

## When to Use

### Keep Development Mode if:
- You're actively developing/testing themes
- You're testing new features
- You're debugging issues
- You want changes visible immediately

### Switch to Production Mode if:
- Your tracker is stable
- You have high traffic
- You want to reduce server load
- You don't update frequently

## Switching to Production Mode

To re-enable aggressive caching for performance:

1. Edit `/install/extra/config.phpsample.php`
2. Change cache expiration times back to higher values:
   ```php
   $INSTALLER09['expires']['user_cache'] = 30 * 86400; // 30 days
   $INSTALLER09['expires']['curuser'] = 30 * 86400; // 30 days
   ```
3. Comment out the no-cache headers in `.htaccess`
4. Clear Redis cache: `/tools/clear_redis_cache.php`

## Monitoring Cache

Check cache statistics at runtime:
```php
$mc1 = new CACHE();
$stats = $mc1->stats();
echo "Cache Hits: " . $stats['hits'];
echo "Cache Misses: " . $stats['misses'];
echo "Current Items: " . $stats['curr_items'];
```

## Files Modified

1. `/install/extra/config.phpsample.php` - Cache TTL reductions
2. `/templates/1/template.php` - Cache-busting headers & URL params
3. `/templates/2/template.php` - Cache-busting headers & URL params
4. `/include/class/class_cache.php` - New flush_all() & flush_keys_pattern() methods
5. `/.htaccess` - Apache no-cache headers
6. `/tools/clear_redis_cache.php` - New cache management tool

## Key Benefits

✅ **Immediate Updates:** Template changes visible instantly  
✅ **Real-time Testing:** Theme modifications take effect immediately  
✅ **No Cache Conflicts:** Old cached versions won't interfere  
✅ **Fresh Content:** Users always see current data  
✅ **Easy Reset:** Can flush cache with one command  
✅ **Flexible:** Can still be tuned per application need

## Important Notes

⚠️ **Development Mode Only:** This configuration is ideal for development but will increase server load in production  
⚠️ **Requires Redis:** Cache flushing commands need Redis to be running  
⚠️ **Admin-Only Access:** Cache clearing tool restricted to administrators  
⚠️ **Browser Cache:** Make sure to refresh with Ctrl+F5 if changes don't appear immediately

## Quick Commands

### Clear all cache:
```bash
php tools/clear_redis_cache.php
```

### Clear specific user cache:
```bash
# Visit /tools/clear_user_cache.php?user_id=1
```

### Check current configuration:
```bash
grep -r "expires\[" install/extra/config.phpsample.php
```

---

**Configured:** December 2025  
**Mode:** Development (Relaxed Caching)  
**Redis:** Required for cache operations
