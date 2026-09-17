import { ok, route } from "@/lib/api";
import { listCities, listEvents } from "@/lib/queries";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

export const GET = route(async (req: Request) => {
  const url = new URL(req.url);
  const [events, cities] = await Promise.all([
    listEvents({
      q: url.searchParams.get("q") ?? undefined,
      city: url.searchParams.get("city") ?? undefined,
      category: url.searchParams.get("category") ?? undefined,
    }),
    listCities(),
  ]);
  return ok({ events, cities: cities.map((c) => c.city) });
});
