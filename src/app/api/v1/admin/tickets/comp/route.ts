import { z } from "zod";
import { ok, route, readJson, requireRole } from "@/lib/api";
import { compTicket } from "@/lib/admin";

export const runtime = "nodejs";

const compSchema = z.object({
  event_id: z.coerce.number().int().positive(),
  seat_id: z.coerce.number().int().positive(),
  customer_email: z.string().email(),
});

export const POST = route(async (req: Request) => {
  const admin = await requireRole("admin");
  const input = compSchema.parse(await readJson<unknown>(req));
  const ticket = await compTicket(admin, input.event_id, input.seat_id, input.customer_email);
  return ok({ ticket }, 201);
});
