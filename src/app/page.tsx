import Link from "next/link";
import { getSessionUser } from "@/lib/auth";
import { listCities, listEvents } from "@/lib/queries";
import { Footer, Header, StatusChip } from "@/components/ui";

export const dynamic = "force-dynamic";

function fmtDate(d: string) {
  return new Date(d).toLocaleString("en-IN", {
    weekday: "short",
    day: "numeric",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export default async function HomePage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; city?: string; category?: string }>;
}) {
  const sp = await searchParams;
  const [session, events, cities] = await Promise.all([
    getSessionUser(),
    listEvents({ q: sp.q, city: sp.city, category: sp.category }),
    listCities(),
  ]);

  const categories = ["Music", "Comedy", "Tech"];

  return (
    <div className="flex min-h-screen flex-col">
      <Header session={session} />

      {/* Hero */}
      <section className="bg-gradient-to-r from-ink-blue to-sky-teal text-white">
        <div className="mx-auto max-w-6xl px-4 py-14">
          <h1 className="font-display text-3xl font-bold sm:text-4xl">
            Find your seat. Keep the memory.
          </h1>
          <p className="mt-2 max-w-xl text-white/85">
            Interactive seat maps, fair pricing locked at checkout, and QR entry — all backed by a
            fully audited PostgreSQL booking engine.
          </p>
          <form action="/" className="mt-6 flex flex-wrap gap-2">
            <input
              name="q"
              defaultValue={sp.q}
              placeholder="Search events…"
              className="input max-w-xs border-transparent text-ink"
            />
            <select name="city" defaultValue={sp.city ?? ""} className="input max-w-[160px] border-transparent text-ink">
              <option value="">All cities</option>
              {cities.map((c) => (
                <option key={c.city} value={c.city}>
                  {c.city}
                </option>
              ))}
            </select>
            <select name="category" defaultValue={sp.category ?? ""} className="input max-w-[150px] border-transparent text-ink">
              <option value="">All categories</option>
              {categories.map((c) => (
                <option key={c} value={c}>
                  {c}
                </option>
              ))}
            </select>
            <button className="btn bg-white font-semibold text-ink-blue hover:bg-mint-mist" type="submit">
              Search
            </button>
          </form>
        </div>
      </section>

      <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-10">
        <div className="mb-6 flex items-center justify-between">
          <h2 className="font-display text-xl font-semibold">
            Upcoming events{" "}
            <span className="text-sm font-normal text-ink-soft">
              ({events.length})
            </span>
          </h2>
          {(sp.q || sp.city || sp.category) && (
            <Link href="/" className="text-sm text-ink-blue hover:underline">
              Clear filters
            </Link>
          )}
        </div>

        {events.length === 0 ? (
          <div className="card p-12 text-center text-ink-soft">
            No events match your filters. <Link href="/" className="text-ink-blue hover:underline">Reset</Link>
          </div>
        ) : (
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {events.map((e) => (
              <Link key={e.id} href={`/events/${e.id}`} className="card group overflow-hidden transition hover:shadow-[var(--shadow-lift)]">
                <div className="flex h-28 items-end bg-gradient-to-br from-ink-blue to-sky-teal p-4">
                  <span className="chip bg-white/90 text-ink-blue">{e.category}</span>
                </div>
                <div className="p-5">
                  <h3 className="font-display text-lg font-semibold leading-snug group-hover:text-ink-blue">
                    {e.title}
                  </h3>
                  <p className="mt-1 text-sm text-ink-soft">
                    {fmtDate(e.starts_at)} · {e.venue_name}, {e.city}
                  </p>
                  <div className="mt-4 flex items-center justify-between">
                    <span className="tnum text-sm font-semibold text-ink-blue">
                      {e.min_price ? `from ₹${Number(e.min_price).toLocaleString("en-IN")}` : "Free"}
                    </span>
                    <span className="text-xs text-ink-soft">
                      {e.capacity - e.tickets_sold} seats left
                    </span>
                  </div>
                  <div className="mt-3">
                    <StatusChip status={e.status} />
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </main>
      <Footer />
    </div>
  );
}
