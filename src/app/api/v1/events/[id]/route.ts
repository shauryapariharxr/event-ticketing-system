import { ok, route, ApiError } from "@/lib/api";
import { getEvent } from "@/lib/queries";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

export const GET = route(async (_req: Request, ctx: { params: Promise<{ id: string }> }) => {
  const { id } = await ctx.params;
  const event = await getEvent(Number(id));
  if (!event) throw new ApiError(404, "Event not found");
  return ok({ event });
});
