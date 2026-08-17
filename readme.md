# Event Ticketing System

A PHP + MySQL web application for browsing events, booking seats, handling mock payments, and managing venues/events through an admin panel.

## Features

- **Event browsing** — public event listing and detail pages
- **Interactive seat selection** — seats grouped by section and row, live price total, and selection legend
- **Booking flow** — seat hold → mock payment → ticket generation
- **Ticket validation** — gate-side ticket check-in
- **Admin panel** — manage events, venues, seats, refunds, users, and view reports
- **Role-based access** — Admin, Organizer, Customer, and Gate roles
- **Audit logging** — key actions (bookings, etc.) are recorded

## Tech Stack

- **Backend:** PHP (procedural, PDO for MySQL)
- **Database:** MySQL
- **Frontend:** Bootstrap 5 + custom CSS, vanilla JavaScript
- **Local dev:** XAMPP (Apache + MySQL)

## Project Structure

```
├── admin/              # Admin panel (dashboard, events, venues, seats, refunds, users, reports)
├── public/              # Public-facing pages (browse, book, pay, tickets, auth)
├── includes/            # Shared PHP: config, db connection, auth, session, layout partials
├── assets/
│   ├── css/style.css     # App styling
│   └── js/app.js         # Client-side interactivity (seat selection, confirmations)
├── database/
│   ├── schema/schema.sql # Database schema
│   ├── schema/seed.sql   # Seed/demo data
│   ├── queries/          # Reference queries
│   └── backup/           # DB backups
├── uploads/              # Uploaded assets (e.g. event images)
└── docs/installation.md  # Setup instructions
```

## Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)

### Installation

1. Copy this project folder into your XAMPP `htdocs` directory.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open phpMyAdmin and run `database/schema/schema.sql` to create the database and tables.
4. Run `database/schema/seed.sql` to load demo data.
5. Update database credentials in `includes/config.php` if they differ from the defaults.
6. Visit `http://localhost/event_ticketing_seating/public/setup.php` and click **Reset Demo Passwords**.
7. Visit `http://localhost/event_ticketing_seating/public/` to use the app.

### Demo Accounts

All demo users share the password `password` after running setup:

| Role      | Email                  |
|-----------|-------------------------|
| Admin     | admin@example.com       |
| Organizer | organizer@example.com   |
| Customer  | customer@example.com    |
| Gate      | gate@example.com        |

## Roadmap / Planned Improvements

- [ ] Booking hold countdown timer
- [ ] Loading/disabled states on payment and login forms
- [ ] Event filtering and sorting (date, city, price)
- [ ] QR code generation on tickets
- [ ] Admin sidebar with grouped navigation
- [ ] Inline form validation
