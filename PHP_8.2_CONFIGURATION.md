# PHP 8.2 Configuration Recommendations

## PHP.ini Settings for U-232 V5

### Required Settings

```ini
; Enable mysqli extension
extension=mysqli

; Character encoding
default_charset = "UTF-8"

; Memory and execution limits
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

; File uploads (for torrents, avatars)
upload_max_filesize = 50M
post_max_size = 52M

; Session settings
session.save_handler = files
session.use_cookies = 1
session.use_only_cookies = 1
session.cookie_httponly = 1
session.cookie_samesite = Lax

; Error reporting (development)
display_errors = On
display_startup_errors = On
error_reporting = E_ALL

; Error reporting (production)
; display_errors = Off
; display_startup_errors = Off
; error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
; log_errors = On
; error_log = /path/to/php-error.log

; Timezone (adjust to your location)
date.timezone = "UTC"

; mysqli settings
mysqli.default_socket = 
mysqli.default_host = localhost
mysqli.default_user = 
mysqli.default_pw = 
mysqli.reconnect = Off
```

## Apache Configuration (.htaccess)

```apache
# PHP 8.2 Settings
<IfModule mod_php8.c>
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit 256M
    php_value post_max_size 52M
    php_value upload_max_filesize 50M
    php_flag display_errors Off
    php_flag log_errors On
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Deny access to sensitive files
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

<FilesMatch "\.(txt|log|md|sh|sql|backup)$">
    Require all denied
</FilesMatch>

# Allow specific markdown files
<FilesMatch "^(README|LICENSE|CHANGELOG)\.md$">
    Require all granted
</FilesMatch>

# Rewrite rules (if using mod_rewrite)
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Force HTTPS (optional, uncomment if using SSL)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

## Nginx Configuration

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/u232;
    index index.php index.html;
    
    # PHP 8.2 FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeouts
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~* \.(txt|log|md|sh|sql|backup)$ {
        deny all;
    }
    
    # Allow specific files
    location ~* ^/(README|LICENSE|CHANGELOG)\.md$ {
        allow all;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    
    # Security: Limit request body size
    client_max_body_size 50M;
}
```

## MySQL/MariaDB Configuration

### Recommended my.cnf Settings

```ini
[mysqld]
# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Performance
max_connections = 500
max_allowed_packet = 64M
innodb_buffer_pool_size = 1G

# Query cache (if available, deprecated in MySQL 8.0+)
# query_cache_type = 1
# query_cache_size = 64M

# Slow query log (for debugging)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2

[client]
default-character-set = utf8mb4

[mysql]
default-character-set = utf8mb4
```

### Create Database

```sql
CREATE DATABASE u232_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'u232_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON u232_tracker.* TO 'u232_user'@'localhost';
FLUSH PRIVILEGES;
```

## File Permissions

### Linux/Unix

```bash
# Set ownership (adjust user/group as needed)
chown -R www-data:www-data /var/www/u232

# Set directory permissions
find /var/www/u232 -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/u232 -type f -exec chmod 644 {} \;

# Writable directories (cache, uploads, etc.)
chmod -R 775 /var/www/u232/cache
chmod -R 775 /var/www/u232/uploads
chmod -R 775 /var/www/u232/imdb/cache
chmod -R 775 /var/www/u232/xbt/files

# Config file (should not be web-accessible)
chmod 600 /var/www/u232/include/config.php
```

## Environment-Specific Configurations

### Development Environment

```php
// include/config.php additions
define('SQL_DEBUG', 2);  // Full debug mode
define('PHP_DEBUG', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
```

### Production Environment

```php
// include/config.php additions
define('SQL_DEBUG', 0);  // No debug output
define('PHP_DEBUG', false);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log');
```

## Cron Jobs

### Recommended Cron Configuration

```bash
# Edit crontab
crontab -e

# Add these lines (adjust paths as needed)

# Clean up every 15 minutes
*/15 * * * * /usr/bin/php /var/www/u232/include/cronclean.php >/dev/null 2>&1

# Update stats every hour
0 * * * * /usr/bin/php /var/www/u232/scripts/update_stats.php >/dev/null 2>&1

# Backup database daily at 3 AM
0 3 * * * /usr/bin/php /var/www/u232/admin/backup.php >/dev/null 2>&1

# Clean old torrents weekly
0 2 * * 0 /usr/bin/php /var/www/u232/scripts/clean_torrents.php >/dev/null 2>&1
```

## PHP Extensions Required

```bash
# Check installed extensions
php -m

# Required extensions:
- mysqli
- mbstring
- gd or imagick
- curl
- json
- session
- filter
- pcre
- zlib
- xml
- dom
- SimpleXML

# Optional but recommended:
- opcache
- redis
- zip
```

## Performance Optimization

### OPcache Configuration

```ini
[opcache]
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.validate_timestamps=1
```

### Redis (if using)

```ini
[redis]
redis.session.locking_enabled=0
redis.session.lock_retries=10
redis.session.lock_wait_time=10000
```

**Redis Server Configuration** (`/etc/redis/redis.conf`):
```conf
# Bind to localhost (or your IP)
bind 127.0.0.1

# Set max memory and eviction policy
maxmemory 256mb
maxmemory-policy allkeys-lru

# Enable persistence (optional)
save 900 1
save 300 10
save 60 10000

# Security - set password (recommended)
# requirepass your_redis_password
```

## Security Recommendations

### 1. Disable Dangerous Functions

```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### 2. Hide PHP Version

```ini
expose_php = Off
```

### 3. Session Security

```ini
session.cookie_httponly = 1
session.cookie_secure = 1  ; If using HTTPS
session.use_strict_mode = 1
session.cookie_samesite = "Strict"
```

### 4. File Upload Security

```ini
file_uploads = On
upload_tmp_dir = /tmp
upload_max_filesize = 50M
max_file_uploads = 20
```

## SSL/TLS Configuration (if using HTTPS)

### Let's Encrypt (Certbot)

```bash
# Install certbot
apt-get install certbot python3-certbot-apache

# Get certificate
certbot --apache -d example.com -d www.example.com

# Auto-renewal (already setup by certbot)
certbot renew --dry-run
```

### Apache SSL

```apache
<VirtualHost *:443>
    ServerName example.com
    DocumentRoot /var/www/u232
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/example.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/example.com/privkey.pem
    
    # Modern SSL configuration
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on
</VirtualHost>
```

## Monitoring and Logging

### Error Log Locations

```bash
# PHP error log
/var/log/php/error.log

# Apache error log
/var/log/apache2/error.log

# Nginx error log
/var/log/nginx/error.log

# MySQL error log
/var/log/mysql/error.log

# Application logs (U-232)
/var/www/u232/logs/
```

### Log Rotation

```bash
# /etc/logrotate.d/u232
/var/www/u232/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
    postrotate
        /etc/init.d/apache2 reload > /dev/null
    endscript
}
```

## Troubleshooting

### Common Issues

1. **White Page/500 Error**
   - Check PHP error log
   - Verify file permissions
   - Check database connection

2. **Database Connection Failed**
   - Verify MySQL is running: `service mysql status`
   - Check credentials in config.php
   - Verify database exists

3. **Session Issues**
   - Check session.save_path is writable
   - Verify cookie settings
   - Check browser cookies enabled

4. **File Upload Fails**
   - Check upload_max_filesize
   - Check post_max_size
   - Verify upload directory permissions

## Verification Checklist

- [ ] PHP 8.2+ installed
- [ ] mysqli extension enabled
- [ ] Database created with utf8mb4
- [ ] File permissions set correctly
- [ ] Error logging configured
- [ ] Cron jobs setup
- [ ] SSL configured (if using HTTPS)
- [ ] All required extensions installed
- [ ] Config.php updated with correct credentials
- [ ] Test database connection
- [ ] Test file uploads
- [ ] Test user registration/login
- [ ] Check error logs for issues

---

**Note:** Adjust all paths, usernames, and passwords according to your environment.
