import { ok, route, requireRole } from "@/lib/api";
import { listAuditLogs, listRefunds } from "@/lib/admin";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

export const GET = route(async (req: Request) => {
  await requireRole("admin");
  const url = new URL(req.url);
  const limit = Math.min(Number(url.searchParams.get("limit") ?? 100), 500);
  const [logs, refunds] = await Promise.all([listAuditLogs(limit), listRefunds()]);
  return ok({ logs, refunds });
});
