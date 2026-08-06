# Event Ticketing & Seating Management System

A beginner-friendly DBMS PBL project built with plain PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap 5.

## Features

- Role-based authentication for Administrator, Organizer, Customer, and Gate Staff
- Venue, section, row, and seat management
- Event management with section-wise ticket pricing
- Customer event browsing, seat selection, booking, mock payment, printable tickets, cancellation, and refunds
- Gate staff ticket validation with scan logging
- Admin dashboard, users, refunds, reports, and audit logs
- MySQL schema with 15 tables, foreign keys, constraints, indexes, views, triggers, stored procedure, and 20+ sample queries

## Setup

1. Place this folder inside XAMPP `htdocs`.
2. Start Apache and MySQL.
3. Open phpMyAdmin.
4. Run `database/schema/schema.sql`.
5. Run `database/schema/seed.sql`.
6. Open `http://localhost/event_ticketing_seating/public/setup.php`.
7. Click `Reset Demo Passwords`.
8. Open `http://localhost/event_ticketing_seating/public/`.

## Demo Accounts

All seeded accounts use password `password` after running `public/setup.php` once.

| Role | Email |
| --- | --- |
| Administrator | `admin@example.com` |
| Organizer | `organizer@example.com` |
| Customer | `customer@example.com` |
| Gate Staff | `gate@example.com` |

## Folder Structure

```text
admin/              Admin and organizer pages
assets/             CSS, JavaScript, images
database/schema/    Schema and seed SQL
database/queries/   Report and learning queries
docs/               Installation notes
includes/           Shared PHP configuration, DB, auth, helpers, layout
public/             Public customer-facing pages
uploads/            Upload target placeholder
```

## Notes

- No external PHP framework is used.
- All database writes use PDO prepared statements.
- Booking and payment logic uses database transactions.
- Seat duplication is prevented by application checks and MySQL triggers.
