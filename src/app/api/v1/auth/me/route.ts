import { ok, route, requireUser } from "@/lib/api";

export const runtime = "nodejs";

export const GET = route(async () => {
  const user = await requireUser();
  return ok({ user });
});
