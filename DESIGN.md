# Design Specification — StagePass UI/UX

A modern, clean interface that keeps the focus on **events and seats**: generous whitespace, soft depth, one accent-driven action per screen. Built on the provided color scheme.

---

## 1. Color System

Anchor color from the brief: `#66A3BF`. Adjacent bands in the palette image were sampled as best-effort approximations — they are defined as tokens so they can be swapped in one place (`src/app/globals.css`).

| Token | Hex | Usage |
|-------|-----|-------|
| `--ink-blue` | `#2B5E8C` | Primary brand: headers, primary buttons, active nav, links |
| `--sky-teal` | `#66A3BF` | Secondary: seat-available, highlights, icons, focus rings, charts |
| `--mint-mist` | `#D9E8E3` | Backgrounds: page sections, cards, seat-sold contrast, success tints |
| `--warm-ivory` | `#F7F3EC` | App background, neutral surfaces |
| `--surface` | `#FFFFFF` | Cards, modals, inputs |
| `--text-primary` | `#1F2A33` | Headings, body |
| `--text-secondary` | `#5B6B76` | Captions, meta |
| `--danger` | `#C0504D` | Errors, denied scans, cancel-event |
| `--warning` | `#D9A441` | Holds counting down, pending payments |
| `--success` | `#3E8E6E` | Granted scans, paid, confirmed |

**Semantic mapping (core UX device):** seat states use the palette directly —
- Available: `--surface` with `--sky-teal` border
- Selected: `--ink-blue` fill, white icon
- Held (someone else): `--mint-mist` fill, disabled
- Sold: `--text-secondary` at 25% opacity, not clickable
- Tier tiers are differentiated by *shape/row group + legend*, not new hues (keeps the palette clean).

Dark text on light surfaces maintains ≥ 4.5:1 contrast (WCAG AA). `--ink-blue` on `--warm-ivory` ≈ 5.6:1.

## 2. Typography

- **Display / headings:** "Sora" or "Manrope" (Google Fonts) — geometric, modern.
- **Body / UI:** "Inter".
- Scale: `34/28/22/18/16/14/13px`; line-height 1.2 headings, 1.6 body.
- Numbers (prices, counts, timers) use `tabular-nums` so hold countdowns don't jitter.

## 3. Spacing, Shape & Elevation

- 8px grid: `4/8/12/16/24/32/48/64`.
- Radii: cards `16px`, buttons/inputs `10px`, seats `6px`, pills `999px`.
- Shadows: single soft recipe `0 1px 2px rgba(31,42,51,.06), 0 8px 24px rgba(31,42,51,.08)`; hover raises slightly. No heavy drop shadows.
- Motion: 150–200ms ease-out; seat selection pops (scale 1→1.05→1); hold timer bar depletes smoothly.

## 4. Core Components

| Component | Specification |
|-----------|--------------|
| **SeatMap** | SVG-based, venue layout scaled to container; legend on top (Available / Selected / Held / Sold + tier groups); click to toggle select (max per order enforced); sticky summary bar with count + total; hold countdown chip turns `--warning` under 2 min |
| **EventCard** | 16:9 gradient header (`--ink-blue`→`--sky-teal`), category pill, title, date/venue meta, price-from, availability meter |
| **Button** | Primary `--ink-blue`; secondary ghost with `--sky-teal` border; destructive `--danger`; all 40px min height |
| **Input / Select** | White surface, 1px `#E3E8EB` border, focus ring `--sky-teal` 2px |
| **QR Ticket card** | White card, event header band, QR (PNG data-URL), ticket number in mono, status chip, perforated divider |
| **Status chips** | Pending `--warning`, Active `--success`, Used/Scanned neutral, Cancelled `--danger`, Comp italic |
| **Toast** | Bottom-right, 4s, role-based colors; booking success includes "View ticket" action |
| **Data table** | Admin: sticky header, row hover `--mint-mist` 40%, zebra optional, inline status chips, column sort |
| **Stat card** | Big tabular number, delta arrow, 7-day sparkline in `--sky-teal` |
| **Modal / Drawer** | Confirmations (cancel event, release hold); slide-over drawer for event editing |

## 5. Pages — Customer

1. **Home / Browse** — hero search (query, city, category), filter chips, responsive event grid, empty state with reset.
2. **Event detail** — hero band, description, tier pricing table, venue mini-map, prominent **"Select seats"** CTA.
3. **Seat selection** — full SeatMap, tier legend, running total, hold notice ("Seats held for 10:00"), checkout button disabled until ≥1 seat.
4. **Checkout** — order summary (rule 4: prices fixed), mock payment form, pay button; success → ticket page.
5. **My Tickets** — grid of QR ticket cards; filter by upcoming/past; transfer action.
6. **Auth** — centered card on `--mint-mist` backdrop; login/register toggle.

## 6. Pages — Admin Panel (`/admin`, role-gated)

Shell: fixed sidebar (logo, Dashboard, Events, Venues, Scans, Users, Audit Log), top bar with admin identity. Content on `--warm-ivory`, cards on `--surface`.

1. **Dashboard** — 4 stat cards (Revenue, Tickets sold, Active events, Scans today), revenue-by-day bar chart, occupancy-by-event table, live scan feed (granted/denied chips).
2. **Events** — table with status filter; create/edit drawer (venue picker, datetime with validation per rule 1, tier editor with tax/fee fields); **Cancel event** flow with explicit refund policy confirmation (rule 8).
3. **Venues** — list + layout builder-lite: add sections/rows/seat counts, live preview of generated seat map (rule 2 uniqueness surfaced inline).
4. **Scan portal** — QR paste/scan input, gate selector, immediate granted/denied result with reason (rule 7), session scan history.
5. **Users** — list, role badges, ticket counts.
6. **Audit Log** (rule 10) — filterable table (actor, action, entity), expandable JSON diff (before/after), append-only notice.

## 6b. States & Empty/Edge UX

- Loading: skeleton cards (no spinners) for lists; subtle pulsing `--mint-mist`.
- Seat map conflict ("seat just taken"): auto-deselect with inline toast + refreshed availability.
- Hold expiry: timer hits 0 → seats released, gentle modal with "Pick seats again".
- Errors: inline field errors in `--danger`, never alert(); API errors surfaced as toasts with retry where sensible.
- Denied scan result: full-height `--danger` banner with reason (already scanned / wrong gate / cancelled event / unknown code).

## 7. Responsive & Accessibility

- Breakpoints: `sm 640 / md 768 / lg 1024 / xl 1280`. Seat map scrolls horizontally on mobile with fixed legend; admin collapses sidebar to icon rail under `lg`.
- Full keyboard support: seats are focusable buttons (`aria-pressed`, arrow-key grid navigation); modals trap focus; visible focus ring `--sky-teal`.
- Screen-reader labels for seat cells: "Row F Seat 12, Tier A, available".
- Color is never the only signal — states also differ by icon/shape/label.
