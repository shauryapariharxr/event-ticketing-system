import { z } from "zod";
import { ok, route, readJson, requireRole } from "@/lib/api";
import { createEvent, listAllEvents } from "@/lib/admin";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const tierSchema = z.object({
  name: z.string().min(1),
  base_price: z.number().nonnegative(),
  tax_rate: z.number().min(0).max(1),
  fee_amount: z.number().nonnegative(),
  quantity: z.number().int().positive(),
});

const eventSchema = z.object({
  venue_id: z.number().int().positive(),
  title: z.string().min(2),
  description: z.string().default(""),
  category: z.string().min(2).default("Music"),
  starts_at: z.string(),
  ends_at: z.string(),
  reentry_allowed: z.boolean().default(false),
  entry_gate: z.string().optional(),
  tiers: z.array(tierSchema).min(1, "At least one tier required"),
});

export const GET = route(async () => {
  await requireRole("admin", "organizer");
  return ok({ events: await listAllEvents() });
});

export const POST = route(async (req: Request) => {
  const admin = await requireRole("admin");
  const input = eventSchema.parse(await readJson<unknown>(req));
  const id = await createEvent(admin, input);
  return ok({ id }, 201);
});
