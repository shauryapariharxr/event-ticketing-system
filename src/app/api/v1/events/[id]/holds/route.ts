import { ok, route, readJson, requireUser } from "@/lib/api";
import { holdSchema, holdSeats } from "@/lib/booking";

export const runtime = "nodejs";

export const POST = route(async (req: Request, ctx: { params: Promise<{ id: string }> }) => {
  const user = await requireUser();
  const { id } = await ctx.params;
  const input = holdSchema.parse(await readJson<unknown>(req));
  const result = await holdSeats(user, Number(id), input.seat_ids);
  return ok(result, 201);
});
