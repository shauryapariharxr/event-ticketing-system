import { ok, route, requireUser } from "@/lib/api";
import { listMyTickets } from "@/lib/queries";
import { qrDataUrl } from "@/lib/qr";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

export const GET = route(async () => {
  const user = await requireUser();
  const tickets = await listMyTickets(user.id);
  const withQr = await Promise.all(
    tickets.map(async (t) => ({ ...t, qr_image: await qrDataUrl(t.qr_code) }))
  );
  return ok({ tickets: withQr });
});
