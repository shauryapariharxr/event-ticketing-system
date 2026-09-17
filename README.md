# 🎟️ StagePass — Event Ticketing & Seating

A full-stack event ticketing platform: browse events, pick seats on an **interactive seat map**, pay, receive **QR tickets**, and validate entry at gates — with a complete **admin panel** for managing venues, events, sales, scans, and audits.

Built for the DBMS PBL (CSL_311) brief *"Event Ticketing & Seating"* on a **local PostgreSQL** database, with every one of the 10 business rules enforced at the database and service layer.

## ✨ Features

**Customer**
- Browse events with search, city & category filters
- Interactive SVG seat map with tiers, availability states, and 10-minute seat holds
- Atomic checkout: price/tax/fee snapshot locked at purchase, mock payment, instant ticket issuance
- QR tickets with transfer support

**Gate / Organizer**
- Scan portal: validate QR codes with reason-coded grants/denials (already scanned, wrong gate, cancelled event)
- Optional re-entry per event

**Admin**
- Dashboard: revenue, tickets sold, occupancy, live scan feed
- Manage venues (seat layout builder), events, ticket tiers, users
- Complimentary ticket issuance, event cancellation with refund records
- Append-only audit log of every sale, assignment, scan, transfer, and refund

## 🧱 Tech Stack

| Layer | Tech |
|-------|------|
| Framework | Next.js 15 (App Router, TypeScript) — UI + REST API in one app |
| Database | PostgreSQL 16 (local), raw SQL migrations, `pg` pool |
| Auth | JWT in httpOnly cookies, `middleware.ts` route gating, bcrypt |
| Jobs | `node-cron` hold sweeper started from `instrumentation.ts` |
| Styling | Tailwind CSS with custom palette tokens (see DESIGN.md) |

## 🚀 Getting Started

### Prerequisites
- Node.js 20+
- PostgreSQL 16 running locally (service or Docker)

### 1. Create the database
```bash
# using psql (adjust credentials as needed)
createdb stagepass
```

### 2. Run the app (single Next.js project)
```bash
npm install
cp .env.example .env        # set DATABASE_URL=postgres://postgres:postgres@localhost:5432/stagepass
npm run db:migrate          # runs src/db/migrations/*.sql in order
npm run db:seed             # demo venues, seats, events, tiers, users
npm run dev                 # http://localhost:3000 · admin panel at /admin
```

### Demo accounts (from seed)
| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@stagepass.test` | `Admin@123` |
| Customer | `customer@stagepass.test` | `Customer@123` |

## 📁 Project Structure

See `IMPLEMENTATION_PLAN.md` §2 for the full tree. Short version:

```
src/app/(customer)/    storefront: browse, event detail, seat map, checkout, my tickets
src/app/admin/         admin panel: dashboard, events, venues, scans, users, audit log
src/app/api/v1/        REST route handlers: auth, events, holds, bookings, scans, admin
src/db/                pg pool · SQL migrations · seeds
src/lib/               auth & rbac · booking engine · scan service · audit · QR
src/instrumentation.ts node-cron hold sweeper
```

Planning docs (README.md · IMPLEMENTATION_PLAN.md · DESIGN.md) live in the project root.

## 🔗 API Overview

Base URL: `/api/v1` — full table in `IMPLEMENTATION_PLAN.md` §5.

- `POST /auth/register` · `POST /auth/login`
- `GET /events` · `GET /events/:id` · `GET /venues/:id/seats`
- `POST /events/:id/holds` · `DELETE /holds/:id` · `POST /bookings/checkout`
- `GET /tickets/mine` · `POST /tickets/:id/transfer`
- `POST /scans/validate`
- `/admin/*` — events, venues, tiers, comps, cancel+refunds, stats, audit log

## 📏 Business Rules (1–10) — enforced

1. Events require a valid venue + ordered start/end times → FK + `CHECK`
2. Seat IDs unique per venue/layout → composite `UNIQUE`
3. No double-sell per event → transaction + partial unique index
4. Price/tax/fee frozen at purchase → snapshot columns on `tickets`
5. Valid only after payment/comp → status flip inside the payment transaction
6. Unique ticket number + QR code → `UNIQUE` columns, opaque signed token
7. Single successful scan (unless re-entry) → guarded insert + partial index
8. Cancelled events stop sales + refunds → status gate + refund records
9. Holds expire automatically → cron sweeper + server-side re-check
10. Full audit trail → `audit_logs` written on every mutation

## 📚 Docs

- [`IMPLEMENTATION_PLAN.md`](./IMPLEMENTATION_PLAN.md) — architecture, schema, API, phases, testing
- [`DESIGN.md`](./DESIGN.md) — color system (`#2B5E8C`, `#66A3BF`, `#D9E8E3`, `#F7F3EC`), typography, components, page specs

## 🗺️ Roadmap

- [ ] Phases 0–6 per implementation plan
- [ ] Real payment provider adapter (Stripe test mode)
- [ ] Organizer role portal (own events only)
- [ ] Email delivery of QR tickets
"# event-ticketing-system" 
