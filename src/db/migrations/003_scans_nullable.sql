-- Unknown QR codes must still be audit-logged (Rule 10), so scans.ticket_id becomes nullable.
ALTER TABLE scans ALTER COLUMN ticket_id DROP NOT NULL;
