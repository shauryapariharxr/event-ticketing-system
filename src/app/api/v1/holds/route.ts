import { z } from "zod";
import { ok, route, readJson, requireUser } from "@/lib/api";
import { q } from "@/db/pool";
import { releaseHolds } from "@/lib/booking";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

export const GET = route(async () => {
  const user = await requireUser();
  const rows = await q(
    `SELECT h.id, h.event_id, h.seat_id, h.expires_at,
            e.title AS event_title, s.section, s.row_label, s.seat_number, s.tier_name
     FROM seat_holds h
     JOIN events e ON e.id = h.event_id
     JOIN seats s ON s.id = h.seat_id
     WHERE h.user_id = $1 AND h.expires_at > now()
     ORDER BY h.expires_at`,
    [user.id]
  );
  return ok({ holds: rows });
});

const releaseSchema = z.object({ hold_ids: z.array(z.number().int().positive()).min(1) });

export const DELETE = route(async (req: Request) => {
  const user = await requireUser();
  const input = releaseSchema.parse(await readJson<unknown>(req));
  const released = await releaseHolds(user, input.hold_ids);
  return ok({ released });
});
