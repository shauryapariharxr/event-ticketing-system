import { z } from "zod";
import { ok, route, readJson, requireRole } from "@/lib/api";
import { listVenues } from "@/lib/queries";
import { createVenue } from "@/lib/admin";
import type { SessionUser } from "@/lib/auth";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const sectionSchema = z.object({
  name: z.string().min(1),
  tier: z.string().min(1),
  rows: z.array(z.string().min(1)).min(1),
  perRow: z.number().int().min(1).max(30),
});

const venueSchema = z.object({
  name: z.string().min(2),
  address: z.string().min(2),
  city: z.string().min(2),
  sections: z.array(sectionSchema).min(1),
});

export const GET = route(async () => {
  return ok({ venues: await listVenues() });
});

export const POST = route(async (req: Request) => {
  const admin = await requireRole("admin");
  const input = venueSchema.parse(await readJson<unknown>(req));
  const venue = await createVenue(admin as SessionUser, input);
  return ok({ venue }, 201);
});
