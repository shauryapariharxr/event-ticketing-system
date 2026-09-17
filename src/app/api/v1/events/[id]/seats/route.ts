import { ok, route, ApiError } from "@/lib/api";
import { getSeatMap } from "@/lib/queries";
import { getSessionUser } from "@/lib/auth";
import { sweepExpired } from "@/lib/booking";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

export const GET = route(async (_req: Request, ctx: { params: Promise<{ id: string }> }) => {
  const { id } = await ctx.params;
  await sweepExpired(); // Rule 9: expire before presenting availability
  const user = await getSessionUser();
  const seats = await getSeatMap(Number(id), user?.id);
  if (seats === null) throw new ApiError(404, "Event not found");
  return ok({ seats });
});
