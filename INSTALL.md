# U-232 V5 Fresh Installation Guide

## Prerequisites

### Server Requirements
- **Operating System:** Linux (Ubuntu 20.04+, Debian 11+, CentOS 8+) or Windows Server
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **PHP:** 8.0+ (8.2+ recommended)
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **Disk Space:** 1GB minimum (more for torrents and uploads)
- **RAM:** 512MB minimum (1GB+ recommended)

### Required PHP Extensions
```bash
# Check if extensions are installed
php -m | grep -E 'mysqli|mbstring|gd|curl|json|session|filter'
```

Required:
- mysqli
- mbstring
- curl
- json
- session
- filter
- pcre
- zlib

Recommended:
- gd or imagick
- opcache
- redis
- zip

---

## Step 1: Install Server Software

### Ubuntu/Debian

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache, PHP 8.2, MySQL, Redis
sudo apt install -y apache2 mysql-server redis-server
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-curl php8.2-gd php8.2-xml php8.2-zip php8.2-opcache php8.2-redis

# Enable required Apache modules
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

### CentOS/RHEL

```bash
# Update system
sudo yum update -y

# Install EPEL and Remi repository
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Enable PHP 8.2
sudo yum module reset php -y
sudo yum module enable php:remi-8.2 -y

# Install packages
sudo yum install -y httpd mariadb-server redis
sudo yum install -y php php-cli php-mysqlnd php-mbstring php-curl \
    php-gd php-xml php-zip php-opcache php-redis

# Start services
sudo systemctl start httpd
sudo systemctl enable httpd
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### Windows

1. Download and install [XAMPP](https://www.apachefriends.org/) with PHP 8.2
2. Or install individual components:
   - [Apache](https://httpd.apache.org/download.cgi)
   - [PHP 8.2](https://windows.php.net/download/)
   - [MySQL/MariaDB](https://mariadb.org/download/)

---

## Step 2: Database Setup

### Secure MySQL Installation

```bash
sudo mysql_secure_installation
```

Follow prompts:
- Set root password: **Yes**
- Remove anonymous users: **Yes**
- Disallow root login remotely: **Yes**
- Remove test database: **Yes**
- Reload privilege tables: **Yes**

### Create Database and User

```bash
# Login to MySQL
sudo mysql -u root -p

# Or on some systems
sudo mysql
```

```sql
-- Create database
CREATE DATABASE u232_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace 'your_password' with a strong password)
CREATE USER 'u232_user'@'localhost' IDENTIFIED BY 'your_strong_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON u232_tracker.* TO 'u232_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify
SHOW DATABASES;
SELECT User, Host FROM mysql.user WHERE User = 'u232_user';

-- Exit
EXIT;
```

### Test Database Connection

```bash
mysql -u u232_user -p u232_tracker
# Enter password when prompted
# If successful, you'll see mysql> prompt
# Type: EXIT;
```

---

## Step 3: Download and Extract Files

### Using Git (Recommended)

```bash
# Navigate to web directory
cd /var/www/html
# Or on Ubuntu: cd /var/www
# Or on XAMPP Windows: cd C:\xampp\htdocs

# Clone repository
sudo git clone https://github.com/Bigjoos/U-232-V5.git u232

# Or download your modified version
# Upload the U-232-V5 folder to your server
```

### Using FTP/Upload

1. Download/compress your U-232-V5 folder
2. Upload to server via FTP/SCP
3. Extract if needed

### Set Ownership (Linux)

```bash
# Replace www-data with your web server user
# Ubuntu/Debian: www-data
# CentOS/RHEL: apache
# Find your user: ps aux | grep apache

sudo chown -R www-data:www-data /var/www/html/u232
```

---

## Step 4: Install Database Schema

### Locate SQL Files

Check the `install/` directory for database schema files:

```bash
cd /var/www/html/u232/install
ls -la *.sql
```

### Import Database Schema

```bash
# Import main schema (adjust filename as needed)
mysql -u u232_user -p u232_tracker < install/u232_schema.sql

# If schema is split into multiple files
mysql -u u232_user -p u232_tracker < install/01_structure.sql
mysql -u u232_user -p u232_tracker < install/02_data.sql

# Or import via phpMyAdmin:
# 1. Access phpMyAdmin
# 2. Select u232_tracker database
# 3. Click Import tab
# 4. Choose .sql file
# 5. Click Go
```

### Verify Tables Created

```bash
mysql -u u232_user -p u232_tracker -e "SHOW TABLES;"
```

You should see tables like: users, torrents, peers, categories, etc.

---

## Step 5: Configure Application

### Copy Configuration Template

```bash
cd /var/www/html/u232

# Copy config template
cp install/extra/config.phpsample.php include/config.php

# Set permissions
chmod 600 include/config.php
```

### Edit Configuration

```bash
nano include/config.php
# Or use: vi, vim, or any text editor
```

### Essential Settings

```php
<?php
/**
 * Main Configuration File
 */

// Database Settings
$INSTALLER09['mysql_host'] = 'localhost';
$INSTALLER09['mysql_user'] = 'u232_user';
$INSTALLER09['mysql_pass'] = 'your_strong_password_here';
$INSTALLER09['mysql_db'] = 'u232_tracker';

// Site Settings
$INSTALLER09['baseurl'] = 'http://your-domain.com';  // NO trailing slash
$INSTALLER09['site_name'] = 'Your Tracker Name';
$INSTALLER09['site_email'] = 'admin@your-domain.com';

// Announce URL
$INSTALLER09['announce_url'] = 'http://your-domain.com/announce.php';

// Paths (usually correct by default)
define('ROOT_PATH', dirname(__FILE__, 2) . DIRECTORY_SEPARATOR);
define('INCL_DIR', ROOT_PATH . 'include' . DIRECTORY_SEPARATOR);
define('CACHE_DIR', ROOT_PATH . 'cache' . DIRECTORY_SEPARATOR);
define('CLASS_DIR', ROOT_PATH . 'class' . DIRECTORY_SEPARATOR);

// Security
$INSTALLER09['tracker_post_key'] = 'your_random_string_here_make_it_long_and_random';
$INSTALLER09['site_online'] = true;

// Timezone
$INSTALLER09['time_offset'] = '0';  // UTC offset in hours
date_default_timezone_set('UTC');  // Or your timezone

// Debug (set to 0 in production)
define('SQL_DEBUG', 1);  // 0=off, 1=logged, 2=displayed

// Cookie settings
$INSTALLER09['cookie_prefix'] = 'u232_';
$INSTALLER09['cookie_path'] = '/';
$INSTALLER09['cookie_domain'] = '';  // Leave empty or set to .your-domain.com

// More configuration below...
```

### Generate Random Security Keys

```bash
# Generate random string for tracker_post_key
openssl rand -base64 32

# Or in PHP
php -r "echo bin2hex(random_bytes(32));"
```

---

## Step 6: Set File Permissions

### Linux Permissions

```bash
cd /var/www/html/u232

# Set base permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Writable directories
chmod -R 775 cache/
chmod -R 775 uploads/
chmod -R 775 torrents/
chmod -R 775 imdb/cache/
chmod -R 775 logs/

# Secure config
chmod 600 include/config.php

# If announce config exists
chmod 600 include/ann_config.php

# Set ownership
sudo chown -R www-data:www-data .
```

### Windows Permissions

1. Right-click folders: cache, uploads, torrents, imdb/cache, logs
2. Properties → Security
3. Edit → Add → IUSR and IIS_IUSRS
4. Grant "Modify" permissions

---

## Step 7: Configure Web Server

### Apache Configuration

Create VirtualHost configuration:

```bash
sudo nano /etc/apache2/sites-available/u232.conf
```

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/html/u232
    
    <Directory /var/www/html/u232>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # PHP settings
        php_value upload_max_filesize 50M
        php_value post_max_size 52M
        php_value max_execution_time 300
        php_value memory_limit 256M
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/u232_error.log
    CustomLog ${APACHE_LOG_DIR}/u232_access.log combined
    
    # Security headers
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

Enable site and restart:

```bash
sudo a2ensite u232.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
```

### Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/u232
```

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/html/u232;
    index index.php index.html;
    
    client_max_body_size 50M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    
    location ~ /\. {
        deny all;
    }
    
    location ~* \.(txt|log|md|sh|sql|backup)$ {
        deny all;
    }
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    error_log /var/log/nginx/u232_error.log;
    access_log /var/log/nginx/u232_access.log;
}
```

Enable and restart:

```bash
sudo ln -s /etc/nginx/sites-available/u232 /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## Step 8: Create Admin Account

### Via Installation Script

If U-232 has an installation wizard:

```bash
# Access in browser
http://your-domain.com/install/

# Follow installation wizard
# Delete install directory after completion:
rm -rf install/
```

### Manual Database Insert

```sql
# Login to MySQL
mysql -u u232_user -p u232_tracker

# Insert admin user (adjust values)
INSERT INTO users (
    username, 
    passhash, 
    secret, 
    email, 
    status, 
    added, 
    class, 
    enabled
) VALUES (
    'admin',
    MD5('your_password'),  -- Change password after first login!
    MD5(RAND()),
    'admin@your-domain.com',
    'confirmed',
    UNIX_TIMESTAMP(),
    7,  -- UC_SYSOP class
    'yes'
);

# Get user ID
SELECT id FROM users WHERE username = 'admin';

# Exit
EXIT;
```

---

## Step 9: Configure Cron Jobs

Cron jobs are essential for tracker maintenance.

```bash
# Edit crontab
crontab -e

# Add these lines (adjust paths):
*/15 * * * * /usr/bin/php /var/www/html/u232/include/cronclean.php >/dev/null 2>&1
0 * * * * /usr/bin/php /var/www/html/u232/scripts/update_stats.php >/dev/null 2>&1
0 3 * * * /usr/bin/php /var/www/html/u232/admin/backup.php >/dev/null 2>&1
```

Common cron jobs:
- **cronclean.php** - Clean up peers, update torrents (every 15 min)
- **update_stats.php** - Update statistics (hourly)
- **backup.php** - Database backup (daily)

---

## Step 10: Initial Testing

### Test Database Connection

Create test file: `test_db.php`

```php
<?php
require_once 'include/bittorrent.php';

try {
    dbconn();
    echo "✓ Database connected successfully!<br>";
    echo "✓ PHP Version: " . PHP_VERSION . "<br>";
    echo "✓ mysqli extension: " . (extension_loaded('mysqli') ? 'Loaded' : 'NOT loaded') . "<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
```

Access: `http://your-domain.com/test_db.php`

**Delete this file after testing!**

### Test Main Page

Access: `http://your-domain.com/`

You should see the tracker homepage.

### Test Login

1. Go to: `http://your-domain.com/login.php`
2. Login with admin credentials
3. Verify you can access admin panel

### Test Announce

```bash
# Should return a valid response (not "Invalid passkey")
curl "http://your-domain.com/announce.php?info_hash=12345678901234567890&peer_id=12345678901234567890&port=6881&uploaded=0&downloaded=0&left=0&torrent_pass=test"
```

---

## Step 11: Security Hardening

### 1. Remove Installation Files

```bash
rm -rf install/
rm test_db.php  # If you created it
```

### 2. Secure Configuration Files

```bash
chmod 600 include/config.php
chmod 600 include/ann_config.php
```

### 3. Configure Firewall

```bash
# UFW (Ubuntu/Debian)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# FirewallD (CentOS/RHEL)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 4. Disable PHP Functions (Optional)

Edit `php.ini`:

```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

### 5. Enable HTTPS (Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache
# Or for Nginx: python3-certbot-nginx

# Get certificate
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Auto-renewal test
sudo certbot renew --dry-run
```

Update `baseurl` in config.php to use `https://`

---

## Step 12: Post-Installation Configuration

### Site Settings

Login to admin panel and configure:

1. **General Settings**
   - Site name, description
   - Registration (open/closed/invite-only)
   - Upload/download rules

2. **Tracker Settings**
   - Announce interval
   - Peer timeout
   - Max peers per torrent

3. **User Classes**
   - Configure user levels
   - Set upload/download limits

4. **Categories**
   - Add torrent categories
   - Set icons and descriptions

5. **Forum Settings**
   - Create forums and topics
   - Set permissions

### Create Cache Files

Some systems require initial cache generation:

```bash
cd /var/www/html/u232
sudo -u www-data php admin/cache_maker.php
```

---

## Troubleshooting

### Problem: White Page/500 Error

**Solution:**
```bash
# Check PHP error log
tail -f /var/log/php/error.log
# Or Apache error log
tail -f /var/log/apache2/error.log

# Enable error display (development only)
nano include/config.php
# Add:
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Problem: Database Connection Failed

**Solution:**
```bash
# Test MySQL connection
mysql -u u232_user -p u232_tracker

# Check credentials in config.php
nano include/config.php

# Verify user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'u232_user'@'localhost';"
```

### Problem: Permission Denied

**Solution:**
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/html/u232

# Fix permissions
sudo find /var/www/html/u232 -type d -exec chmod 755 {} \;
sudo find /var/www/html/u232 -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/html/u232/cache
```

### Problem: Upload Fails

**Solution:**
```bash
# Check PHP settings
php -i | grep -E 'upload_max_filesize|post_max_size|max_execution_time'

# Edit php.ini
sudo nano /etc/php/8.2/apache2/php.ini
# Or for Nginx: /etc/php/8.2/fpm/php.ini

# Set:
upload_max_filesize = 50M
post_max_size = 52M
max_execution_time = 300

# Restart web server
sudo systemctl restart apache2
# Or: sudo systemctl restart php8.2-fpm
```

### Problem: Can't Login/Session Issues

**Solution:**
```bash
# Check session.save_path is writable
php -r "echo session_save_path();"

# Make it writable
sudo chmod 1733 /var/lib/php/sessions
# Or set custom path in php.ini:
session.save_path = "/var/www/html/u232/sessions"
sudo mkdir /var/www/html/u232/sessions
sudo chmod 775 /var/www/html/u232/sessions
sudo chown www-data:www-data /var/www/html/u232/sessions
```

---

## Migration from Existing Installation

If you're upgrading from an older U-232 installation or PHP version, follow these steps:

### Step 1: Backup Everything

**Critical: Always backup before migration!**

```bash
# Backup database
mysqldump -u u232_user -p u232_tracker > u232_backup_$(date +%Y%m%d).sql

# Backup files
cd /var/www/html
tar -czf u232_backup_$(date +%Y%m%d).tar.gz u232/

# Store backups safely
mkdir -p ~/backups
mv u232_backup_*.* ~/backups/
```

### Step 2: Check PHP Version Compatibility

```bash
# Check current PHP version
php -v

# If PHP < 8.0, you need to upgrade PHP first
```

### Step 3: Upgrade PHP (if needed)

**Ubuntu/Debian:**
```bash
# Add PHP repository
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Install PHP 8.2
sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-curl php8.2-gd php8.2-xml php8.2-zip php8.2-opcache php8.2-redis

# For Apache
sudo a2dismod php7.4  # Or your current version
sudo a2enmod php8.2
sudo systemctl restart apache2

# For Nginx
# Update php-fpm socket in nginx config
sudo nano /etc/nginx/sites-available/u232
# Change: fastcgi_pass unix:/run/php/php8.2-fpm.sock;
sudo systemctl restart nginx php8.2-fpm
```

**CentOS/RHEL:**
```bash
# Enable Remi repository
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Install PHP 8.2
sudo yum module reset php
sudo yum module enable php:remi-8.2
sudo yum install -y php php-mysqlnd php-mbstring php-curl php-gd php-xml

sudo systemctl restart httpd
```

### Step 4: Update Database to UTF8MB4

```sql
-- Backup first, then convert database
mysql -u u232_user -p

-- Check current charset
SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME 
FROM information_schema.SCHEMATA 
WHERE SCHEMA_NAME = 'u232_tracker';

-- Convert database
ALTER DATABASE u232_tracker CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Convert all tables (this may take time for large databases)
-- Generate conversion commands
SELECT CONCAT('ALTER TABLE ', table_name, ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'u232_tracker';

-- Execute the generated commands, or use this script:
-- Create a file: convert_tables.sql

-- Apply it:
-- mysql -u u232_user -p u232_tracker < convert_tables.sql

EXIT;
```

### Step 5: Update Code Files

**Option A: Manual Update (Recommended for Custom Modifications)**

```bash
cd /var/www/html/u232

# Keep your old files
mv include/bittorrent.php include/bittorrent.php.old
mv include/user_functions.php include/user_functions.php.old
mv announce.php announce.php.old

# Copy new modernized files
# (Upload the modernized files from the PHP 8.2 version)
# Then merge any custom modifications from .old files
```

**Option B: Run Migration Helper**

```bash
cd /var/www/html/u232

# Run the migration helper on your codebase
php migration_helper.php .

# Review changes (backups created as .backup files)
# Test thoroughly
```

**Option C: Fresh Code with Data Migration**

```bash
# Extract new code to temporary location
cd /var/www/html
mv u232 u232_old
git clone https://github.com/Bigjoos/U-232-V5.git u232

# Copy custom files/modifications
cp u232_old/include/config.php u232/include/
cp -r u232_old/uploads/* u232/uploads/
cp -r u232_old/torrents/* u232/torrents/

# Your database stays the same - just update code
```

### Step 6: Update Configuration File

```bash
cd /var/www/html/u232

# Compare old and new config templates
diff include/config.php install/extra/config.phpsample.php

# Add any new required settings
nano include/config.php
```

**New settings to add if missing:**

```php
// Ensure these are set for PHP 8.2
define('SQL_DEBUG', 0);  // Set to 1 for debugging, 0 for production

// Timezone
date_default_timezone_set('UTC');  // Or your timezone

// Error handling
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . 'logs/php_errors.log');
```

### Step 7: Apply Database Schema Updates

Check for any database updates/migrations:

```bash
cd /var/www/html/u232

# Check for update SQL files
ls -la install/updates/*.sql

# Apply updates (if any)
mysql -u u232_user -p u232_tracker < install/updates/update_to_php82.sql
```

If no update files exist, check the changelog for required schema changes.

### Step 8: Clear Cache and Regenerate

```bash
cd /var/www/html/u232

# Clear cache files
rm -rf cache/*.php
rm -rf cache/*.cache

# Regenerate cache
php admin/cache_maker.php

# Or clear Redis cache if needed
redis-cli FLUSHDB
```

### Step 9: Test Migration

Create a test file: `test_migration.php`

```php
<?php
require_once 'include/bittorrent.php';

echo "=== Migration Test ===<br><br>";

// Test database connection
try {
    dbconn();
    echo "✓ Database connection: OK<br>";
} catch (Exception $e) {
    echo "✗ Database connection: FAILED - " . $e->getMessage() . "<br>";
}

// Check PHP version
echo "✓ PHP Version: " . PHP_VERSION . "<br>";

// Check extensions
$extensions = ['mysqli', 'mbstring', 'curl', 'gd', 'json'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    echo "$status Extension $ext: " . (extension_loaded($ext) ? 'Loaded' : 'NOT loaded') . "<br>";
}

// Test query
try {
    $result = sql_query("SELECT COUNT(*) as count FROM users");
    $row = mysqli_fetch_assoc($result);
    echo "✓ User count: " . $row['count'] . "<br>";
} catch (Exception $e) {
    echo "✗ Query test: FAILED - " . $e->getMessage() . "<br>";
}

// Test cache directory
echo is_writable('cache') ? '✓' : '✗';
echo " Cache directory: " . (is_writable('cache') ? 'Writable' : 'NOT writable') . "<br>";

echo "<br>=== Test Complete ===<br>";
?>
```

Access: `http://your-domain.com/test_migration.php`

**Delete this file after testing!**

### Step 10: Update Cron Jobs

```bash
# Edit crontab
crontab -e

# Ensure PHP 8.2 is being used
# Update paths if needed:
*/15 * * * * /usr/bin/php8.2 /var/www/html/u232/include/cronclean.php >/dev/null 2>&1

# Or find PHP 8.2 path
which php8.2

# Or use generic php if it's 8.2+
which php
php -v
```

### Step 11: Monitor for Issues

```bash
# Watch error logs
tail -f /var/log/apache2/error.log
tail -f /var/www/html/u232/logs/php_errors.log

# Check SQL debug log if enabled
tail -f /var/www/html/u232/logs/sql_errors.log

# Monitor for deprecated warnings
grep -i "deprecated" /var/log/php/error.log
```

### Step 12: Gradual Rollout (Recommended)

**For Production Sites:**

1. **Test Environment First**
   - Setup test server with copy of production
   - Complete full migration on test
   - Test all features thoroughly
   - Let beta users test

2. **Maintenance Mode**
   ```php
   // In config.php - enable during migration
   $INSTALLER09['site_online'] = false;
   ```

3. **Migration Window**
   - Choose low-traffic time
   - Notify users in advance
   - Have rollback plan ready

4. **Gradual Feature Testing**
   - Test login/logout
   - Test torrent upload/download
   - Test announce
   - Test admin functions
   - Test user registration
   - Test forums and PM system

5. **Rollback Plan**
   ```bash
   # If issues occur, rollback:
   cd /var/www/html
   rm -rf u232
   tar -xzf ~/backups/u232_backup_YYYYMMDD.tar.gz
   
   # Restore database
   mysql -u u232_user -p u232_tracker < ~/backups/u232_backup_YYYYMMDD.sql
   ```

### Migration Troubleshooting

**Issue: Fatal Errors After Migration**

```bash
# Enable error display temporarily
nano include/config.php
# Add:
ini_set('display_errors', 1);
error_reporting(E_ALL);

# Check syntax errors
php -l include/bittorrent.php
```

**Issue: Database Queries Failing**

```bash
# Check for old mysql_* function calls
grep -r "mysql_query" --include="*.php" .
grep -r "mysql_connect" --include="*.php" .

# Should all be mysqli_* now
```

**Issue: Sessions Not Working**

```bash
# Check session path
php -r "echo session_save_path();"

# Make writable
sudo chmod 1733 /var/lib/php/sessions
```

**Issue: Performance Degradation**

```bash
# Enable OPcache
nano /etc/php/8.2/apache2/php.ini

# Add/uncomment:
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000

sudo systemctl restart apache2
```

### Post-Migration Optimization

```bash
# Optimize database tables
mysql -u u232_user -p u232_tracker -e "OPTIMIZE TABLE users, torrents, peers, comments;"

# Rebuild indexes if needed
mysql -u u232_user -p u232_tracker -e "ANALYZE TABLE users, torrents, peers;"

# Clear OPcache
# Create: clear_opcache.php
<?php
opcache_reset();
echo "OPcache cleared";
?>

# Access it once, then delete
```

### Migration Checklist

- [ ] Full backup completed (database + files)
- [ ] PHP 8.2+ installed and active
- [ ] Database converted to utf8mb4
- [ ] Code files updated/migrated
- [ ] Configuration file updated
- [ ] Cache cleared and regenerated
- [ ] File permissions verified
- [ ] Test migration script passed
- [ ] Cron jobs updated
- [ ] Error logs monitored
- [ ] All features tested
- [ ] Performance verified
- [ ] Rollback plan ready
- [ ] Users notified (if applicable)
- [ ] Backup of old version kept

---

## Verification Checklist

- [ ] PHP 8.2+ installed and working
- [ ] mysqli extension loaded
- [ ] MySQL/MariaDB running
- [ ] Database created with utf8mb4
- [ ] User account created with permissions
- [ ] Database schema imported
- [ ] Config.php configured correctly
- [ ] File permissions set (755/644)
- [ ] Writable directories set (775)
- [ ] Web server configured
- [ ] Site accessible in browser
- [ ] Can login with admin account
- [ ] Cron jobs configured
- [ ] Install directory removed
- [ ] Error logs accessible
- [ ] HTTPS configured (optional but recommended)

---

## Quick Start Summary

```bash
# 1. Install software
sudo apt install apache2 mysql-server redis-server php8.2 php8.2-mysql php8.2-mbstring php8.2-redis

# 2. Setup database
sudo mysql -e "CREATE DATABASE u232_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'u232_user'@'localhost' IDENTIFIED BY 'password';"
sudo mysql -e "GRANT ALL ON u232_tracker.* TO 'u232_user'@'localhost';"

# 3. Extract files
cd /var/www/html && sudo git clone https://github.com/Bigjoos/U-232-V5.git u232

# 4. Import database
mysql -u u232_user -p u232_tracker < /var/www/html/u232/install/schema.sql

# 5. Configure
sudo cp /var/www/html/u232/install/extra/config.phpsample.php /var/www/html/u232/include/config.php
sudo nano /var/www/html/u232/include/config.php

# 6. Set permissions
sudo chown -R www-data:www-data /var/www/html/u232
sudo chmod -R 775 /var/www/html/u232/cache /var/www/html/u232/uploads

# 7. Access site
# http://your-domain.com/
```

---

## Support

- **Documentation:** See README_PHP82.md for modernization details
- **Quick Reference:** See QUICK_REFERENCE.md
- **Configuration:** See PHP_8.2_CONFIGURATION.md

---

**Installation complete! Your U-232 V5 tracker is ready to use.**

Remember to:
- Change default passwords
- Configure site settings
- Remove install directory
- Enable HTTPS
- Monitor error logs
