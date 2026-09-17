import { q } from "@/db/pool";

export interface EventListItem {
  id: number;
  title: string;
  description: string;
  category: string;
  starts_at: string;
  ends_at: string;
  status: string;
  venue_name: string;
  city: string;
  min_price: string | null;
  tickets_sold: number;
  capacity: number;
}

export async function listEvents(opts: { q?: string; city?: string; category?: string; includeAll?: boolean } = {}) {
  const conditions: string[] = [];
  const params: unknown[] = [];
  if (!opts.includeAll) conditions.push(`e.status = 'scheduled' AND e.ends_at > now()`);
  if (opts.q) {
    params.push(`%${opts.q}%`);
    conditions.push(`(e.title ILIKE $${params.length} OR e.description ILIKE $${params.length})`);
  }
  if (opts.city) {
    params.push(opts.city);
    conditions.push(`v.city = $${params.length}`);
  }
  if (opts.category) {
    params.push(opts.category);
    conditions.push(`e.category = $${params.length}`);
  }
  const where = conditions.length ? `WHERE ${conditions.join(" AND ")}` : "";
  return q<EventListItem>(
    `SELECT e.id, e.title, e.description, e.category, e.starts_at, e.ends_at, e.status,
            v.name AS venue_name, v.city,
            (SELECT MIN(tt.base_price) FROM ticket_tiers tt WHERE tt.event_id = e.id) AS min_price,
            (SELECT COUNT(*)::int FROM tickets t WHERE t.event_id = e.id AND t.status IN ('active','used')) AS tickets_sold,
            v.capacity
     FROM events e
     JOIN venues v ON v.id = e.venue_id
     ${where}
     ORDER BY e.starts_at ASC
     LIMIT 60`,
    params
  );
}

export async function getEvent(id: number) {
  const ev = await q<EventListItem & { reentry_allowed: boolean; entry_gate: string | null; venue_address: string }>(
    `SELECT e.id, e.title, e.description, e.category, e.starts_at, e.ends_at, e.status,
            e.reentry_allowed, e.entry_gate, v.name AS venue_name, v.address AS venue_address,
            v.city, v.capacity,
            (SELECT COUNT(*)::int FROM tickets t WHERE t.event_id = e.id AND t.status IN ('active','used')) AS tickets_sold
     FROM events e JOIN venues v ON v.id = e.venue_id
     WHERE e.id = $1`,
    [id]
  );
  if (!ev[0]) return null;
  const tiers = await q<{
    id: number;
    name: string;
    base_price: string;
    tax_rate: string;
    fee_amount: string;
    quantity: number;
    sold: number;
  }>(
    `SELECT tt.id, tt.name, tt.base_price, tt.tax_rate, tt.fee_amount, tt.quantity,
            (SELECT COUNT(*)::int FROM tickets t WHERE t.tier_id = tt.id AND t.status IN ('active','used')) AS sold
     FROM ticket_tiers tt WHERE tt.event_id = $1 ORDER BY tt.base_price DESC`,
    [id]
  );
  return { ...ev[0], tiers };
}

export interface SeatWithState {
  id: number;
  section: string;
  row_label: string;
  seat_number: number;
  tier_name: string;
  state: "available" | "held" | "sold" | "selected";
}

export async function getSeatMap(eventId: number, userId?: number) {
  const ev = await q<{ venue_id: number }>(`SELECT venue_id FROM events WHERE id = $1`, [eventId]);
  if (!ev[0]) return null;

  const seats = await q<SeatWithState>(
    `SELECT s.id, s.section, s.row_label, s.seat_number, s.tier_name,
            CASE
              WHEN t.id IS NOT NULL THEN 'sold'
              WHEN h.id IS NOT NULL AND h.user_id = $2 THEN 'held'
              WHEN h.id IS NOT NULL THEN 'held'
              ELSE 'available'
            END AS state
     FROM seats s
     LEFT JOIN seat_holds h ON h.seat_id = s.id AND h.event_id = $1 AND h.expires_at > now()
     LEFT JOIN tickets t ON t.seat_id = s.id AND t.event_id = $1 AND t.status IN ('pending','active','used')
     WHERE s.venue_id = $3
     ORDER BY s.section, s.row_label, s.seat_number`,
    [eventId, userId ?? 0, ev[0].venue_id]
  );
  return seats;
}

export async function listVenues() {
  return q<{ id: number; name: string; address: string; city: string; capacity: number; sections: number; seats: number }>(
    `SELECT v.id, v.name, v.address, v.city, v.capacity,
            COUNT(DISTINCT s.section)::int AS sections,
            COUNT(s.id)::int AS seats
     FROM venues v LEFT JOIN seats s ON s.venue_id = v.id
     GROUP BY v.id ORDER BY v.id`
  );
}

export async function getVenueSeats(venueId: number) {
  return q<{ id: number; section: string; row_label: string; seat_number: number; tier_name: string }>(
    `SELECT id, section, row_label, seat_number, tier_name
     FROM seats WHERE venue_id = $1
     ORDER BY section, row_label, seat_number`,
    [venueId]
  );
}

export interface MyTicket {
  id: number;
  ticket_number: string;
  qr_code: string;
  status: string;
  event_id: number;
  event_title: string;
  starts_at: string;
  venue_name: string;
  city: string;
  section: string;
  row_label: string;
  seat_number: number;
  tier_name: string;
  total_price: string;
}

export async function listMyTickets(userId: number) {
  return q<MyTicket>(
    `SELECT t.id, t.ticket_number, t.qr_code, t.status, e.id AS event_id, e.title AS event_title,
            e.starts_at, v.name AS venue_name, v.city, s.section, s.row_label, s.seat_number,
            s.tier_name, t.total_price
     FROM tickets t
     JOIN events e ON e.id = t.event_id
     JOIN venues v ON v.id = e.venue_id
     JOIN seats s ON s.id = t.seat_id
     WHERE t.user_id = $1
     ORDER BY e.starts_at DESC`,
    [userId]
  );
}

export async function listCities() {
  return q<{ city: string }>(`SELECT DISTINCT city FROM venues ORDER BY city`);
}
