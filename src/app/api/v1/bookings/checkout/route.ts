import { z } from "zod";
import { ok, route, readJson, requireUser, ApiError } from "@/lib/api";
import { checkout, checkoutSchema } from "@/lib/booking";

export const runtime = "nodejs";

export const POST = route(async (req: Request) => {
  const user = await requireUser();
  const input = checkoutSchema.parse(await readJson<unknown>(req));
  const last4 = input.card_last4?.slice(-4);
  if (input.method === "card" && !last4) {
    throw new ApiError(422, "Card number (last 4) required for card payments");
  }
  const tickets = await checkout(user, input.hold_ids, input.method, last4);
  return ok({ tickets }, 201);
});
