# Installation

1. Copy this folder into your XAMPP `htdocs` directory.
2. Start Apache and MySQL from the XAMPP control panel.
3. Open phpMyAdmin and run `database/schema/schema.sql`.
4. Run `database/schema/seed.sql`.
5. Update database credentials in `includes/config.php` if needed.
6. Visit `http://localhost/event_ticketing_seating/public/setup.php`.
7. Click `Reset Demo Passwords`.
8. Visit `http://localhost/event_ticketing_seating/public/`.

Demo users all use password `password` after running setup:

- `admin@example.com`
- `organizer@example.com`
- `customer@example.com`
- `gate@example.com`
