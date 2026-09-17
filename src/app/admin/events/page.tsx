"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { StatusChip } from "@/components/ui";

interface EventRow {
  id: number;
  title: string;
  category: string;
  status: string;
  starts_at: string;
  venue_name: string;
  city: string;
  sold: number;
  tier_count: number;
}
interface Venue {
  id: number;
  name: string;
  city: string;
  seats: number;
  sections: number;
}
interface TierDraft {
  name: string;
  base_price: number;
  tax_rate: number;
  fee_amount: number;
  quantity: number;
}

function toLocalInput(d: Date) {
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export default function AdminEventsPage() {
  const [events, setEvents] = useState<EventRow[] | null>(null);
  const [venues, setVenues] = useState<Venue[]>([]);
  const [showCreate, setShowCreate] = useState(false);
  const [msg, setMsg] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [form, setForm] = useState({
    venue_id: 0,
    title: "",
    description: "",
    category: "Music",
    starts_at: toLocalInput(new Date(Date.now() + 7 * 864e5)),
    ends_at: toLocalInput(new Date(Date.now() + 7 * 864e5 + 3 * 36e5)),
    reentry_allowed: false,
    entry_gate: "",
  });
  const [tiers, setTiers] = useState<TierDraft[]>([
    { name: "Standard", base_price: 999, tax_rate: 0.05, fee_amount: 40, quantity: 100 },
  ]);

  const [cancelTarget, setCancelTarget] = useState<EventRow | null>(null);
  const [cancelReason, setCancelReason] = useState("");

  const [compEventId, setCompEventId] = useState(0);
  const [compSeatId, setCompSeatId] = useState("");
  const [compEmail, setCompEmail] = useState("");

  const load = useCallback(async () => {
    const [e, v] = await Promise.all([
      fetch("/api/v1/admin/events").then((r) => r.json()),
      fetch("/api/v1/admin/venues").then((r) => r.json()),
    ]);
    setEvents(e.events ?? []);
    setVenues(v.venues ?? []);
    if (!form.venue_id && v.venues?.[0]) setForm((f) => ({ ...f, venue_id: v.venues[0].id }));
  }, [form.venue_id]);

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function createEvent(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    const res = await fetch("/api/v1/admin/events", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ...form, starts_at: new Date(form.starts_at).toISOString(), ends_at: new Date(form.ends_at).toISOString(), tiers }),
    });
    const data = await res.json();
    if (!res.ok) {
      setError(data.error ?? "Create failed");
      return;
    }
    setMsg(`Event created (#${data.id}).`);
    setShowCreate(false);
    load();
  }

  async function cancelEvent(e: React.FormEvent) {
    e.preventDefault();
    if (!cancelTarget) return;
    const res = await fetch(`/api/v1/admin/events/${cancelTarget.id}/cancel`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ reason: cancelReason }),
    });
    const data = await res.json();
    if (!res.ok) {
      setError(data.error ?? "Cancel failed");
      return;
    }
    setMsg(
      `"${data.title}" cancelled — sales stopped, ${data.refunds} refund(s) raised totalling ₹${Number(data.refundTotal).toLocaleString("en-IN")}.`
    );
    setCancelTarget(null);
    setCancelReason("");
    load();
  }

  async function issueComp(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    const res = await fetch("/api/v1/admin/tickets/comp", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ event_id: Number(compEventId), seat_id: Number(compSeatId), customer_email: compEmail }),
    });
    const data = await res.json();
    if (!res.ok) {
      setError(data.error ?? "Comp failed");
      return;
    }
    setMsg(`Comp ticket ${data.ticket.ticket_number} issued.`);
    setCompSeatId("");
    setCompEmail("");
    load();
  }

  const fmt = (d: string) => new Date(d).toLocaleString("en-IN", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display text-2xl font-bold">Events</h1>
          <p className="text-sm text-ink-soft">Create, monitor, cancel with automatic refunds, and issue comps.</p>
        </div>
        <button className="btn-primary" onClick={() => setShowCreate((s) => !s)}>
          {showCreate ? "Close" : "+ New event"}
        </button>
      </div>

      {msg && <p className="rounded-lg bg-success/10 px-4 py-2.5 text-sm font-medium text-success">{msg}</p>}
      {error && <p className="rounded-lg bg-danger/10 px-4 py-2.5 text-sm text-danger">{error}</p>}

      {showCreate && (
        <form onSubmit={createEvent} className="card space-y-4 p-6">
          <h2 className="font-display text-lg font-semibold">Create event</h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
              <label className="label">Title</label>
              <input required className="input" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
            </div>
            <div>
              <label className="label">Venue</label>
              <select required className="input" value={form.venue_id}
                      onChange={(e) => setForm({ ...form, venue_id: Number(e.target.value) })}>
                {venues.map((v) => (
                  <option key={v.id} value={v.id}>{v.name} — {v.city} ({v.seats} seats)</option>
                ))}
              </select>
            </div>
            <div>
              <label className="label">Category</label>
              <select className="input" value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })}>
                {["Music", "Comedy", "Tech", "Theatre", "Sports"].map((c) => (
                  <option key={c}>{c}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="label">Starts</label>
              <input type="datetime-local" required className="input" value={form.starts_at}
                     onChange={(e) => setForm({ ...form, starts_at: e.target.value })} />
            </div>
            <div>
              <label className="label">Ends (must be after start)</label>
              <input type="datetime-local" required className="input" value={form.ends_at}
                     onChange={(e) => setForm({ ...form, ends_at: e.target.value })} />
            </div>
            <div className="sm:col-span-2">
              <label className="label">Description</label>
              <textarea className="input min-h-20" value={form.description}
                        onChange={(e) => setForm({ ...form, description: e.target.value })} />
            </div>
            <div className="flex items-center gap-2">
              <input id="reentry" type="checkbox" className="h-4 w-4 accent-[#2b5e8c]"
                     checked={form.reentry_allowed}
                     onChange={(e) => setForm({ ...form, reentry_allowed: e.target.checked })} />
              <label htmlFor="reentry" className="text-sm">Allow re-entry (scans not limited to once)</label>
            </div>
            {form.reentry_allowed && (
              <div>
                <label className="label">Entry gate</label>
                <input className="input" placeholder="GATE-A" value={form.entry_gate}
                       onChange={(e) => setForm({ ...form, entry_gate: e.target.value })} />
              </div>
            )}
          </div>

          <div>
            <div className="mb-2 flex items-center justify-between">
              <p className="label mb-0">Ticket tiers</p>
              <button type="button" className="text-sm font-medium text-ink-blue hover:underline"
                      onClick={() => setTiers([...tiers, { name: `Tier ${tiers.length + 1}`, base_price: 500, tax_rate: 0.05, fee_amount: 30, quantity: 50 }])}>
                + Add tier
              </button>
            </div>
            <div className="space-y-2">
              {tiers.map((t, i) => (
                <div key={i} className="grid gap-2 sm:grid-cols-[1.4fr_1fr_1fr_1fr_1fr_auto]">
                  <input className="input" placeholder="Name" value={t.name}
                         onChange={(e) => setTiers(tiers.map((x, j) => (j === i ? { ...x, name: e.target.value } : x)))} />
                  <input className="input" type="number" min={0} placeholder="₹ base" value={t.base_price}
                         onChange={(e) => setTiers(tiers.map((x, j) => (j === i ? { ...x, base_price: Number(e.target.value) } : x)))} />
                  <input className="input" type="number" min={0} max={1} step={0.01} placeholder="tax rate" value={t.tax_rate}
                         onChange={(e) => setTiers(tiers.map((x, j) => (j === i ? { ...x, tax_rate: Number(e.target.value) } : x)))} />
                  <input className="input" type="number" min={0} placeholder="₹ fee" value={t.fee_amount}
                         onChange={(e) => setTiers(tiers.map((x, j) => (j === i ? { ...x, fee_amount: Number(e.target.value) } : x)))} />
                  <input className="input" type="number" min={1} placeholder="qty" value={t.quantity}
                         onChange={(e) => setTiers(tiers.map((x, j) => (j === i ? { ...x, quantity: Number(e.target.value) } : x)))} />
                  <button type="button" className="btn-ghost px-3" title="Remove tier"
                          onClick={() => setTiers(tiers.filter((_, j) => j !== i))}>✕</button>
                </div>
              ))}
            </div>
          </div>

          <button className="btn-primary" type="submit">Create event</button>
        </form>
      )}

      {/* Events table */}
      <div className="card overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-mint-mist/50 text-left text-xs uppercase tracking-wide text-ink-soft">
            <tr>
              <th className="px-5 py-3">Event</th>
              <th className="px-5 py-3">When</th>
              <th className="px-5 py-3">Sold</th>
              <th className="px-5 py-3">Status</th>
              <th className="px-5 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {(events ?? []).map((ev) => (
              <tr key={ev.id} className="border-t border-line hover:bg-warm-ivory/60">
                <td className="px-5 py-3.5">
                  <Link href={`/events/${ev.id}`} className="font-medium hover:text-ink-blue">{ev.title}</Link>
                  <p className="text-xs text-ink-soft">{ev.venue_name} · {ev.category}</p>
                </td>
                <td className="tnum px-5 py-3.5 text-ink-soft">{fmt(ev.starts_at)}</td>
                <td className="tnum px-5 py-3.5">{ev.sold}</td>
                <td className="px-5 py-3.5"><StatusChip status={ev.status} /></td>
                <td className="px-5 py-3.5 text-right">
                  {ev.status === "scheduled" && (
                    <button className="text-sm font-medium text-danger hover:underline"
                            onClick={() => { setCancelTarget(ev); setError(null); }}>
                      Cancel + refund
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Cancel modal */}
      {cancelTarget && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-ink/40 p-4" role="dialog" aria-modal="true">
          <form onSubmit={cancelEvent} className="card w-full max-w-md p-6">
            <h3 className="font-display text-lg font-bold text-danger">Cancel “{cancelTarget.title}”?</h3>
            <p className="mt-2 text-sm text-ink-soft">
              Sales stop immediately, all holds release, and every succeeded payment is refunded automatically.
              This cannot be undone.
            </p>
            <label className="label mt-4">Reason (recorded in the audit log)</label>
            <textarea required minLength={3} className="input" value={cancelReason}
                      placeholder="e.g. Artist unavailable"
                      onChange={(e) => setCancelReason(e.target.value)} />
            <div className="mt-5 flex justify-end gap-2">
              <button type="button" className="btn-ghost" onClick={() => setCancelTarget(null)}>Keep event</button>
              <button type="submit" className="btn-danger">Cancel event &amp; refund</button>
            </div>
          </form>
        </div>
      )}

      {/* Comp ticket */}
      <div className="card p-6">
        <h2 className="font-display text-lg font-semibold">Issue complimentary ticket</h2>
        <p className="text-sm text-ink-soft">Authorized free issuance — recorded as a comp payment (Rule 5).</p>
        <form onSubmit={issueComp} className="mt-4 grid gap-3 sm:grid-cols-[1.2fr_1fr_1.4fr_auto]">
          <select required className="input" value={compEventId} onChange={(e) => setCompEventId(Number(e.target.value))}>
            <option value={0}>Select event…</option>
            {(events ?? []).filter((ev) => ev.status === "scheduled").map((ev) => (
              <option key={ev.id} value={ev.id}>{ev.title}</option>
            ))}
          </select>
          <input required className="input" placeholder="Seat ID (from DB)" value={compSeatId}
                 onChange={(e) => setCompSeatId(e.target.value)} />
          <input required type="email" className="input" placeholder="customer@email.com" value={compEmail}
                 onChange={(e) => setCompEmail(e.target.value)} />
          <button className="btn-primary" type="submit">Issue comp</button>
        </form>
      </div>
    </div>
  );
}
