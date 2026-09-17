import { z } from "zod";
import { ok, route, readJson, requireRole } from "@/lib/api";
import { cancelEvent } from "@/lib/admin";

export const runtime = "nodejs";

const cancelSchema = z.object({ reason: z.string().min(3, "Cancellation reason required") });

export const POST = route(async (req: Request, ctx: { params: Promise<{ id: string }> }) => {
  const admin = await requireRole("admin");
  const { id } = await ctx.params;
  const input = cancelSchema.parse(await readJson<unknown>(req));
  const result = await cancelEvent(admin, Number(id), input.reason);
  return ok(result);
});
