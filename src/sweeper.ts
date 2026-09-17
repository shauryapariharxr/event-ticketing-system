import { sweepExpired } from "@/lib/booking";
import cron from "node-cron";

/** Starts the hold-expiry sweeper (Rule 9) — every 30 seconds, node runtime only. */
export function startSweeper(): void {
  cron.schedule("*/30 * * * * *", async () => {
    try {
      await sweepExpired();
    } catch (e) {
      console.error("[sweeper]", e);
    }
  });
  console.log("[stagepass] hold sweeper started (every 30s)");
}
