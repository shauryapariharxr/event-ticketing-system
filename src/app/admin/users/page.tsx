"use client";

import { useEffect, useState } from "react";

interface UserRow {
  id: number;
  email: string;
  full_name: string;
  role: string;
  created_at: string;
  ticket_count: number;
}

export default function AdminUsersPage() {
  const [users, setUsers] = useState<UserRow[] | null>(null);

  useEffect(() => {
    fetch("/api/v1/admin/users")
      .then((r) => r.json())
      .then((d) => setUsers(d.users ?? []));
  }, []);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="font-display text-2xl font-bold">Users</h1>
        <p className="text-sm text-ink-soft">{users?.length ?? "…"} registered accounts.</p>
      </div>
      <div className="card overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-mint-mist/50 text-left text-xs uppercase tracking-wide text-ink-soft">
            <tr>
              <th className="px-5 py-3">User</th>
              <th className="px-5 py-3">Role</th>
              <th className="px-5 py-3">Tickets</th>
              <th className="px-5 py-3">Joined</th>
            </tr>
          </thead>
          <tbody>
            {(users ?? []).map((u) => (
              <tr key={u.id} className="border-t border-line hover:bg-warm-ivory/60">
                <td className="px-5 py-3.5">
                  <p className="font-medium">{u.full_name}</p>
                  <p className="text-xs text-ink-soft">{u.email}</p>
                </td>
                <td className="px-5 py-3.5">
                  <span className={`chip ${u.role === "admin" ? "bg-ink-blue text-white" : "bg-mint-mist text-ink-blue"}`}>
                    {u.role}
                  </span>
                </td>
                <td className="tnum px-5 py-3.5">{u.ticket_count}</td>
                <td className="tnum px-5 py-3.5 text-ink-soft">
                  {new Date(u.created_at).toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" })}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
