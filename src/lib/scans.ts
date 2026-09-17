import { withTransaction, q } from "@/db/pool";
import { ApiError } from "@/lib/api";
import { audit } from "@/lib/audit";
import type { SessionUser } from "@/lib/auth";

export interface ScanVerdict {
  result: "granted" | "denied";
  reason: string | null;
  ticket?: {
    ticket_number: string;
    holder: string;
    section: string;
    row_label: string;
    seat_number: number;
    tier: string;
  };
  event?: { title: string; starts_at: string };
}

/** Validate a QR at a gate (Rules 6 & 7). Every attempt — granted or denied — is
 *  logged in the same transaction (Rule 10). */
export async function validateScan(
  scanner: SessionUser,
  qrCode: string,
  gateId: string
): Promise<ScanVerdict> {
  if (!qrCode) throw new ApiError(400, "QR code is required");
  if (!gateId) throw new ApiError(400, "Gate ID is required");

  const verdict = await withTransaction<ScanVerdict>(async (tx) => {
    const rows = await tx.query<{
      ticket_id: number | null;
      status: string | null;
      ticket_number: string | null;
      holder: string | null;
      section: string | null;
      row_label: string | null;
      seat_number: number | null;
      tier: string | null;
      event_title: string | null;
      starts_at: string | null;
      event_status: string | null;
      entry_gate: string | null;
      reentry_allowed: boolean | null;
    }>(
      `SELECT t.id AS ticket_id, t.status, t.ticket_number,
              u.full_name AS holder, s.section, s.row_label, s.seat_number,
              s.tier_name AS tier, e.title AS event_title, e.starts_at,
              e.status AS event_status, e.entry_gate, e.reentry_allowed
       FROM (SELECT $1::text AS qr) x
       LEFT JOIN tickets t ON t.qr_code = x.qr
       LEFT JOIN users u   ON u.id = t.user_id
       LEFT JOIN seats s   ON s.id = t.seat_id
       LEFT JOIN events e  ON e.id = t.event_id`,
      [qrCode]
    );
    const t = rows[0];
    const deny = (reason: string, ticketId: number | null = t?.ticket_id ?? null): ScanVerdict => {
      void tx.query(
        `INSERT INTO scans(ticket_id, gate_id, scanned_by, result, reason)
         VALUES ($1, $2, $3, 'denied', $4)`,
        [ticketId, gateId, scanner.id, reason]
      );
      return { result: "denied", reason };
    };

    if (!t || !t.ticket_id) return deny("Unknown ticket code", null);
    if (t.event_status === "cancelled") return deny("Event cancelled");
    if (t.status === "pending") return deny("Payment not completed");
    if (t.status === "cancelled") return deny("Ticket cancelled");
    if (t.status === "used") return deny("Ticket already scanned"); // Rule 7 (re-entry events never reach 'used')

    if (t.entry_gate && t.entry_gate !== gateId) {
      return deny(`Wrong gate — use ${t.entry_gate}`);
    }

    if (!t.reentry_allowed) {
      const prior = await tx.query<{ id: number }>(
        `SELECT id FROM scans WHERE ticket_id = $1 AND result = 'granted' LIMIT 1`,
        [t.ticket_id]
      );
      if (prior[0]) return deny("Ticket already scanned");
    }

    // For re-entry events we keep the ticket 'active' and count granted scans;
    // for single-entry we flip to 'used' permanently.
    if (!t.reentry_allowed) {
      await tx.query(`UPDATE tickets SET status = 'used' WHERE id = $1`, [t.ticket_id]);
    }
    await tx.query(
      `INSERT INTO scans(ticket_id, gate_id, scanned_by, result, reason)
       VALUES ($1, $2, $3, 'granted', NULL)`,
      [t.ticket_id, gateId, scanner.id]
    );

    return {
      result: "granted",
      reason: null,
      ticket: {
        ticket_number: t.ticket_number!,
        holder: t.holder!,
        section: t.section!,
        row_label: t.row_label!,
        seat_number: t.seat_number!,
        tier: t.tier!,
      },
      event: { title: t.event_title!, starts_at: t.starts_at! },
    };
  });

  await audit(scanner, `scan.${verdict.result}`, "scan", null, {
    gate: gateId,
    result: verdict.result,
    reason: verdict.reason,
    ticket: verdict.ticket?.ticket_number ?? null,
  });

  return verdict;
}

/** Recent scan feed for the admin dashboard. */
export async function recentScans(limit = 20) {
  return q(
    `SELECT sc.id, sc.result, sc.reason, sc.gate_id, sc.scanned_at,
            t.ticket_number, e.title AS event_title
     FROM scans sc
     LEFT JOIN tickets t ON t.id = sc.ticket_id
     LEFT JOIN events e  ON e.id = t.event_id
     ORDER BY sc.scanned_at DESC
     LIMIT $1`,
    [limit]
  );
}
