import { z } from "zod";
import { q, withTransaction } from "@/db/pool";
import { ApiError } from "@/lib/api";
import { audit } from "@/lib/audit";
import { generateQrCode, generateTicketNumber } from "@/lib/qr";
import type { SessionUser } from "@/lib/auth";

export const HOLD_MINUTES = Number(process.env.HOLD_MINUTES_DEFAULT ?? 10);
export const MAX_SEATS_PER_ORDER = 8;

export const holdSchema = z.object({
  seat_ids: z
    .array(z.coerce.number().int().positive())
    .min(1, "Select at least one seat")
    .max(MAX_SEATS_PER_ORDER, `Maximum ${MAX_SEATS_PER_ORDER} seats per order`),
});

export const checkoutSchema = z.object({
  hold_ids: z
    .array(z.coerce.number().int().positive())
    .min(1, "No held seats to check out")
    .max(MAX_SEATS_PER_ORDER),
  method: z.enum(["card", "upi"]),
  // Mock provider: any card number works except ones ending in 0002 (simulated failure)
  card_last4: z.string().regex(/^\d{4}$/).optional(),
});

export interface SeatRow {
  id: number;
  venue_id: number;
  section: string;
  row_label: string;
  seat_number: number;
  tier_name: string;
}

export interface TicketRow {
  id: number;
  event_id: number;
  seat_id: number;
  ticket_number: string;
  qr_code: string;
  status: string;
  base_price: string;
  tax_amount: string;
  fee_amount: string;
  total_price: string;
}

/** Release expired holds and stale pending tickets (Rule 9). Safe to call often. */
export async function sweepExpired(): Promise<{ holds: number; pendings: number }> {
  return withTransaction(async (tx) => {
    const holds = await tx.query(
      `DELETE FROM seat_holds WHERE expires_at <= now() RETURNING event_id, seat_id`
    );
    const pendings = await tx.query(
      `UPDATE tickets SET status = 'cancelled'
       WHERE status = 'pending' AND issued_at < now() - interval '15 minutes'
       RETURNING id`
    );
    return { holds: holds.length, pendings: pendings.length };
  });
}

/** Hold seats for a user (Rule 9: expires automatically). */
export async function holdSeats(
  user: SessionUser,
  eventId: number,
  seatIds: number[]
): Promise<{ hold_ids: number[]; expires_at: string }> {
  return withTransaction(async (tx) => {
    const ev = await tx.query<{ id: number; status: string }>(
      `SELECT id, status FROM events WHERE id = $1 FOR UPDATE`,
      [eventId]
    );
    if (!ev[0]) throw new ApiError(404, "Event not found");
    if (ev[0].status !== "scheduled") {
      throw new ApiError(409, "Event is not open for sale", "SALES_CLOSED"); // Rule 8
    }

    const seats = await tx.query<SeatRow & { event_id: number }>(
      `SELECT s.*, e.id AS event_id FROM seats s
       JOIN events e ON e.venue_id = s.venue_id
       WHERE s.id = ANY($1::bigint[]) AND e.id = $2`,
      [seatIds, eventId]
    );
    if (seats.length !== seatIds.length) {
      throw new ApiError(400, "Some seats do not belong to this event's venue");
    }

    const expires = new Date(Date.now() + HOLD_MINUTES * 60_000);
    const holdIds: number[] = [];
    for (const seat of seats) {
      const conflict = await tx.query<{ kind: string }>(
        `SELECT 'hold' AS kind FROM seat_holds
          WHERE event_id = $1 AND seat_id = $2 AND expires_at > now()
         UNION ALL
         SELECT 'ticket' FROM tickets
          WHERE event_id = $1 AND seat_id = $2 AND status IN ('pending','active','used')
         LIMIT 1`,
        [eventId, seat.id]
      );
      if (conflict[0]) {
        throw new ApiError(
          409,
          `Seat ${seat.section}-${seat.row_label}${seat.seat_number} is no longer available`,
          "SEAT_TAKEN" // Rule 3
        );
      }
      const ins = await tx.query<{ id: number }>(
        `INSERT INTO seat_holds(event_id, seat_id, user_id, expires_at)
         VALUES ($1, $2, $3, $4)
         ON CONFLICT (event_id, seat_id) DO UPDATE
           SET user_id = EXCLUDED.user_id, expires_at = EXCLUDED.expires_at
         WHERE seat_holds.user_id = $3 AND seat_holds.expires_at <= now()
         RETURNING id`,
        [eventId, seat.id, user.id, expires]
      );
      if (!ins[0]) {
        throw new ApiError(409, `Seat ${seat.section}-${seat.row_label}${seat.seat_number} is held by another session`, "SEAT_HELD");
      }
      holdIds.push(ins[0].id);
    }
    await audit(user, "holds.created", "event", eventId, { seat_ids: seatIds, expires });
    return { hold_ids: holdIds, expires_at: expires.toISOString() };
  });
}

export async function releaseHolds(user: SessionUser, holdIds: number[]): Promise<number> {
  const rows = await q<{ id: number }>(
    `DELETE FROM seat_holds WHERE id = ANY($1::bigint[]) AND user_id = $2 RETURNING id`,
    [holdIds, user.id]
  );
  return rows.length;
}

/** Atomic checkout: validates holds, snapshots prices (Rule 4), records payment and
 *  activates tickets in ONE transaction (Rules 3, 5, 6). */
export async function checkout(
  user: SessionUser,
  holdIds: number[],
  method: "card" | "upi",
  cardLast4?: string
): Promise<TicketRow[]> {
  return withTransaction(async (tx) => {
    // Lock my non-expired holds
    const holds = await tx.query<{
      id: number;
      event_id: number;
      seat_id: number;
      expires_at: string;
    }>(
      `SELECT id, event_id, seat_id, expires_at FROM seat_holds
       WHERE id = ANY($1::bigint[]) AND user_id = $2 FOR UPDATE`,
      [holdIds, user.id]
    );
    if (holds.length !== holdIds.length) {
      throw new ApiError(409, "Some holds are missing — please select seats again", "HOLD_GONE");
    }

    const now = Date.now();
    for (const h of holds) {
      if (new Date(h.expires_at).getTime() <= now) {
        throw new ApiError(409, "Your seat hold expired — please select seats again", "HOLD_EXPIRED"); // Rule 9
      }
    }

    const eventId = holds[0].event_id;
    if (holds.some((h) => h.event_id !== eventId)) {
      throw new ApiError(400, "All seats in an order must belong to the same event");
    }

    const ev = await tx.query<{ status: string }>(
      `SELECT status FROM events WHERE id = $1 FOR UPDATE`,
      [eventId]
    );
    if (!ev[0] || ev[0].status !== "scheduled") {
      throw new ApiError(409, "Event is not open for sale", "SALES_CLOSED"); // Rule 8
    }

    // Pricing inputs (tier per seat) — snapshotted onto the ticket (Rule 4)
    const priced = await tx.query<{
      seat_id: number;
      tier_id: number;
      base_price: string;
      tax_rate: string;
      fee_amount: string;
    }>(
      `WITH seat_tier AS (
         SELECT s.id AS seat_id,
                (SELECT t.id FROM ticket_tiers t
                  WHERE t.event_id = $2 AND t.name = s.tier_name
                  LIMIT 1) AS tier_id
         FROM seats s
         JOIN events e ON e.venue_id = s.venue_id
         WHERE s.id = ANY($1::bigint[]) AND e.id = $2
       )
       SELECT st.seat_id, tt.id AS tier_id, tt.base_price, tt.tax_rate, tt.fee_amount
       FROM seat_tier st
       JOIN ticket_tiers tt ON tt.id = st.tier_id`,
      [holds.map((h) => h.seat_id), eventId]
    );
    if (priced.length !== holds.length) {
      throw new ApiError(400, "Pricing unavailable for one or more seats");
    }

    const tickets: TicketRow[] = [];
    const paymentIds: number[] = [];
    for (const p of priced) {
      const base = Number(p.base_price);
      const tax = Math.round(base * Number(p.tax_rate) * 100) / 100;
      const fee = Number(p.fee_amount);
      const total = base + tax + fee;

      const mockOk = !(method === "card" && cardLast4 === "0002"); // simulated provider decline
      if (!mockOk) throw new ApiError(402, "Payment declined by provider", "PAYMENT_DECLINED");

      const ticket = await tx.query<TicketRow>(
        `INSERT INTO tickets(event_id, seat_id, tier_id, user_id, ticket_number, qr_code,
           status, base_price, tax_amount, fee_amount, total_price)
         VALUES ($1,$2,$3,$4,$5,$6,'active',$7,$8,$9,$10)
         RETURNING id, event_id, seat_id, ticket_number, qr_code, status,
                   base_price, tax_amount, fee_amount, total_price`,
        [
          eventId, p.seat_id, p.tier_id, user.id,
          generateTicketNumber(), generateQrCode(),
          base, tax, fee, total,
        ]
      );
      // Rule 5: payment row + 'active' status commit together; a failure rolls both back.
      const payment = await tx.query<{ id: number }>(
        `INSERT INTO payments(ticket_id, user_id, method, amount, tax_amount, fee_amount,
           status, provider_ref)
         VALUES ($1,$2,$3,$4,$5,$6,'succeeded',$7) RETURNING id`,
        [ticket[0].id, user.id, method, total, tax, fee, `mock_${Date.now()}`]
      );
      tickets.push(ticket[0]);
      paymentIds.push(payment[0].id);
    }

    await tx.query(`DELETE FROM seat_holds WHERE id = ANY($1::bigint[])`, [holdIds]);

    await audit(user, "sale.completed", "event", eventId, {
      ticket_ids: tickets.map((t) => t.id),
      payment_ids: paymentIds,
      total: tickets.reduce((s, t) => s + Number(t.total_price), 0),
    });
    return tickets;
  });
}
