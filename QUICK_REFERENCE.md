# Quick Reference: PHP 8.2 Migration Patterns

## Common Find & Replace Patterns

### 1. Remove Error Suppression from SQL Queries
```php
// OLD
sql_query($query) or sqlerr(__FILE__, __LINE__);

// NEW  
sql_query($query);  // Throws exception automatically
```

### 2. Update Database Connection
```php
// OLD
if (!@($db = mysqli_connect($host, $user, $pass)) AND @mysqli_query($db, "USE $dbname"))
    err('Error');

// NEW
try {
    $db = mysqli_connect($host, $user, $pass, $dbname);
    if (!$db) throw new Exception('Connection failed');
    mysqli_set_charset($db, 'utf8mb4');
} catch (Exception $e) {
    err('Error');
}
```

### 3. Operators
```php
// Replace AND with &&
$check = ($a == 1) AND ($b == 2);        // OLD
$check = ($a == 1) && ($b == 2);         // NEW

// Replace OR with ||
$check = ($a == 1) OR ($b == 2);         // OLD
$check = ($a == 1) || ($b == 2);         // NEW
```

### 4. Strict Comparisons
```php
// Use === for type-safe comparisons
if ($count == 0)        // OLD
if ($count === 0)       // NEW

if ($value == NULL)     // OLD
if ($value === null)    // NEW
```

### 5. Array Syntax
```php
$arr = array();                // OLD
$arr = [];                     // NEW

$arr = array(1, 2, 3);        // OLD
$arr = [1, 2, 3];             // NEW
```

### 6. Add Type Hints
```php
// OLD
function getUser($id) {
    return sql_query("SELECT * FROM users WHERE id = " . sqlesc($id));
}

// NEW
function getUser(int $id): mysqli_result|bool {
    return sql_query("SELECT * FROM users WHERE id = " . sqlesc($id));
}
```

### 7. Modern Cookie Setting
```php
// OLD
setcookie($name, $value, $expires, $path, $domain, NULL, TRUE);

// NEW
setcookie($name, $value, [
    'expires' => $expires,
    'path' => $path,
    'domain' => $domain,
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

### 8. Email Validation
```php
// OLD
function validemail($email) {
    return preg_match('/^[\w.-]+@([\w.-]+\.)+[a-z]{2,6}$/is', $email);
}

// NEW
function validemail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
```

### 9. Error Handling with Try-Catch
```php
// OLD
$result = sql_query($query) or sqlerr(__FILE__, __LINE__);
if (!$result) die('Query failed');

// NEW
try {
    $result = sql_query($query);
} catch (Exception $e) {
    error_log('Query failed: ' . $e->getMessage());
    // Handle error appropriately
}
```

### 10. Remove @ Error Suppression
```php
// OLD
if (@mysqli_num_rows($result) > 0) { ... }
$file = @fopen($path, 'r');

// NEW
if (mysqli_num_rows($result) > 0) { ... }
try {
    $file = fopen($path, 'r');
} catch (Exception $e) {
    error_log('File open failed: ' . $e->getMessage());
}
```

## Type Hints Quick Reference

### Scalar Types
```php
function example(int $id): bool { }
function example(string $name): string { }
function example(float $price): float { }
function example(bool $active): void { }
```

### Union Types (PHP 8.0+)
```php
function example(string|int $value): array|false { }
function example(mixed $anything): string|null { }
```

### Special Return Types
```php
function noReturn(): never { exit(); }
function nullable(): ?string { return null; }
function voidReturn(): void { echo 'test'; }
```

## Common Mistakes to Avoid

### ❌ DON'T
```php
// Don't suppress errors
@sql_query($query);

// Don't use loose comparison for type-sensitive checks
if ($count == 0)

// Don't use old array syntax in new code
$arr = array();

// Don't ignore exceptions
sql_query($query);  // Exception might be thrown

// Don't use deprecated functions
mysql_query($query);  // Use mysqli instead
```

### ✅ DO
```php
// Handle exceptions properly
try {
    sql_query($query);
} catch (Exception $e) {
    // Handle error
}

// Use strict comparisons
if ($count === 0)

// Use modern array syntax
$arr = [];

// Add type hints to functions
function example(int $id): array { }

// Use mysqli functions
mysqli_query($connection, $query);
```

## Testing Checklist

After making changes:

- [ ] Test database connectivity
- [ ] Test user login
- [ ] Check error logs for exceptions
- [ ] Verify all features work
- [ ] Test on PHP 8.2+
- [ ] Check for deprecated warnings
- [ ] Review all modified functions
- [ ] Backup before deploying

## Quick Commands

### Check PHP Version
```bash
php -v
```

### Check for mysqli Extension
```bash
php -m | grep mysqli
```

### Run Migration Helper
```bash
php migration_helper.php /path/to/project
```

### Find Files with Old Patterns
```bash
# Find files with "or sqlerr"
grep -r "or sqlerr" --include="*.php" .

# Find files with @ error suppression on mysqli
grep -r "@mysqli" --include="*.php" .

# Find files with AND/OR operators
grep -r " AND " --include="*.php" .
grep -r " OR " --include="*.php" .
```

## Resources

- **Migration Guide:** PHP_8.2_MIGRATION_GUIDE.md
- **Full Summary:** PHP_8.2_MIGRATION_SUMMARY.md
- **Helper Script:** migration_helper.php

## Need Help?

1. Check error logs
2. Enable SQL_DEBUG in config
3. Review migration guide
4. Test in development first
5. Keep backups

---
**Quick Start:** Run `php migration_helper.php .` to auto-update common patterns!
