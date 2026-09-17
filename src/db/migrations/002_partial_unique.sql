-- Rule 3 refinement: only LIVE tickets (pending/active/used) block a seat.
-- Cancelled tickets stop blocking so failed/cancelled purchases free the seat,
-- while the ticket and payment rows remain for audit (Rule 10).
ALTER TABLE tickets DROP CONSTRAINT tickets_unique_seat_per_event;

CREATE UNIQUE INDEX tickets_event_seat_live_idx
  ON tickets (event_id, seat_id)
  WHERE status IN ('pending', 'active', 'used');
