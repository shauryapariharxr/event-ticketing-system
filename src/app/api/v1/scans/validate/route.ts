import { z } from "zod";
import { ok, route, readJson, requireRole } from "@/lib/api";
import { validateScan } from "@/lib/scans";

export const runtime = "nodejs";

const scanSchema = z.object({
  qr_code: z.string().min(1),
  gate_id: z.string().min(1),
});

export const POST = route(async (req: Request) => {
  const scanner = await requireRole("scanner", "admin", "organizer");
  const input = scanSchema.parse(await readJson<unknown>(req));
  const verdict = await validateScan(scanner, input.qr_code, input.gate_id);
  return ok(verdict);
});
