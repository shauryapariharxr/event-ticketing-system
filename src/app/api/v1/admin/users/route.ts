import { ok, route, requireRole } from "@/lib/api";
import { listUsers } from "@/lib/admin";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

export const GET = route(async () => {
  await requireRole("admin");
  return ok({ users: await listUsers() });
});
