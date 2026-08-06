# Running The Project

## XAMPP Steps

1. Copy or move this folder into XAMPP `htdocs` as:
   `C:\xampp\htdocs\event_ticketing_seating`
2. Start Apache and MySQL in XAMPP Control Panel.
3. Open phpMyAdmin:
   `http://localhost/phpmyadmin`
4. Import and run:
   `database/schema/schema.sql`
5. Import and run:
   `database/schema/seed.sql`
6. Open:
   `http://localhost/event_ticketing_seating/public/setup.php`
7. Click `Reset Demo Passwords`.
8. Open:
   `http://localhost/event_ticketing_seating/public/index.php`

## Demo Login

All demo accounts use password `password` after step 7.

- Admin: `admin@example.com`
- Organizer: `organizer@example.com`
- Customer: `customer@example.com`
- Gate Staff: `gate@example.com`

## Config

If your folder name is different, update this line in `includes/config.php`:

```php
define('BASE_URL', '/event_ticketing_seating');
```

If MySQL uses a password, update:

```php
define('DB_PASS', '');
```
