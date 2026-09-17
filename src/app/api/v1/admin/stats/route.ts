import { ok, route, requireRole } from "@/lib/api";
import { adminStats, occupancyByEvent } from "@/lib/admin";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

export const GET = route(async () => {
  await requireRole("admin");
  const [stats, occupancy] = await Promise.all([adminStats(), occupancyByEvent()]);
  return ok({ ...stats, occupancy });
});
