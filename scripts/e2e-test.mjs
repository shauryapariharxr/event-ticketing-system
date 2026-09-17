// End-to-end smoke test against a running server (npm run start or npm run dev).
const BASE = process.env.BASE_URL ?? "http://127.0.0.1:3000";
let failures = 0;

function check(name, cond, extra = "") {
  if (cond) console.log(`  ✓ ${name}`);
  else {
    failures++;
    console.log(`  ✗ ${name} ${extra}`);
  }
}

const jars = {};
async function api(name, method, path, body) {
  const res = await fetch(BASE + path, {
    method,
    headers: {
      "Content-Type": "application/json",
      ...(jars[name] ? { Cookie: jars[name] } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  const setCookie = res.headers.get("set-cookie");
  if (setCookie) jars[name] = setCookie.split(";")[0];
  let data = null;
  try { data = await res.json(); } catch { /* html */ }
  return { status: res.status, data };
}

console.log("— Auth —");
let r = await api("cust", "POST", "/api/v1/auth/login", { email: "customer@stagepass.test", password: "Customer@123" });
check("customer login", r.status === 200);
r = await api("admin", "POST", "/api/v1/auth/login", { email: "admin@stagepass.test", password: "Admin@123" });
check("admin login", r.status === 200);
r = await api("scan", "POST", "/api/v1/auth/login", { email: "scanner@stagepass.test", password: "Scanner@123" });
check("scanner login", r.status === 200);
r = await api("anon", "POST", "/api/v1/events/1/holds", { seat_ids: [1] });
check("anonymous hold rejected (401)", r.status === 401);

console.log("— Browse —");
r = await api("cust", "GET", "/api/v1/events");
check("events list", r.status === 200 && r.data.events.length >= 4);
const ev = r.data.events[0];
r = await api("cust", "GET", `/api/v1/events/${ev.id}/seats`);
check("seat map", r.status === 200 && r.data.seats.length > 50, `got ${r.data.seats?.length} seats`);
const avail = r.data.seats.filter((s) => s.state === "available").slice(0, 2);
check("available seats exist", avail.length === 2);

console.log("— Hold + checkout —");
r = await api("cust", "POST", `/api/v1/events/${ev.id}/holds`, { seat_ids: avail.map((s) => s.id) });
check("seats held", r.status === 201 && r.data.hold_ids.length === 2, JSON.stringify(r.data));
const holdIds = r.data.hold_ids;

await api("org", "POST", "/api/v1/auth/login", { email: "organizer@stagepass.test", password: "Organizer@123" });
r = await api("org", "POST", `/api/v1/events/${ev.id}/holds`, { seat_ids: [avail[0].id] });
check("same seat blocked for other user (Rule 3/9)", r.status === 409, `got ${r.status}`);

r = await api("cust", "POST", "/api/v1/bookings/checkout", { hold_ids: holdIds, method: "card", card_last4: "4242" });
check("checkout succeeded", r.status === 201 && r.data.tickets.length === 2, JSON.stringify(r.data));
const tickets = r.data.tickets ?? [];
check("price snapshot present (Rule 4)", tickets.every((t) => Number(t.total_price) > 0));
check("unique ticket numbers (Rule 6)", new Set(tickets.map((t) => t.ticket_number)).size === 2);

r = await api("cust", "GET", "/api/v1/tickets/mine");
check("my tickets listed", r.status === 200 && r.data.tickets.length >= 5);

console.log("— Scans (Rule 7) —");
r = await api("scan", "POST", "/api/v1/scans/validate", { qr_code: tickets[0].qr_code, gate_id: "GATE-A" });
check("first scan granted", r.status === 200 && r.data.result === "granted", JSON.stringify(r.data));
r = await api("scan", "POST", "/api/v1/scans/validate", { qr_code: tickets[0].qr_code, gate_id: "GATE-A" });
check("second scan denied", r.data.result === "denied" && r.data.reason === "Ticket already scanned");
r = await api("scan", "POST", "/api/v1/scans/validate", { qr_code: "sp_unknown_code", gate_id: "GATE-A" });
check("unknown code denied", r.data.result === "denied");
r = await api("cust", "POST", "/api/v1/scans/validate", { qr_code: tickets[1].qr_code, gate_id: "GATE-A" });
check("customer cannot scan (RBAC)", r.status === 403);

console.log("— Cancel + refunds (Rule 8) —");
r = await api("admin", "GET", "/api/v1/admin/stats");
check("admin stats", r.status === 200 && Number(r.data.revenue) > 0);
const saleEvents = r.data.occupancy.filter((o) => o.status === "scheduled" && o.sold > 0);
const target = saleEvents[saleEvents.length - 1];
r = await api("admin", "POST", `/api/v1/admin/events/${target.id}/cancel`, { reason: "e2e test cancellation" });
check("event cancelled with refunds", r.status === 200 && r.data.refunds >= 1, JSON.stringify(r.data));
r = await api("cust", "POST", `/api/v1/events/${target.id}/holds`, { seat_ids: [avail[0].id] });
check("sales stopped after cancel", r.status === 409);

console.log("— Audit (Rule 10) —");
r = await api("admin", "GET", "/api/v1/admin/audit?limit=50");
check("audit log has entries", r.status === 200 && r.data.logs.length >= 10);
const actions = r.data.logs.map((l) => l.action);
check("sale audited", actions.includes("sale.completed"));
check("scan audited", actions.some((a) => a.startsWith("scan.")));
check("cancel audited", actions.includes("event.cancelled"));

console.log(failures === 0 ? "\nALL CHECKS PASSED ✅" : `\n${failures} CHECK(S) FAILED ❌`);
process.exit(failures === 0 ? 0 : 1);
