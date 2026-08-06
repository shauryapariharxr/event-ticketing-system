USE event_ticketing_db;

-- 1. Published upcoming events
SELECT * FROM events WHERE status = 'Published' AND event_date >= NOW() ORDER BY event_date;

-- 2. Event with venue details
SELECT e.title, e.event_date, v.name AS venue, v.city FROM events e JOIN venues v ON v.venue_id = e.venue_id;

-- 3. Available seats for one event
SELECT s.seat_id, sec.name AS section_name, r.row_label, s.seat_number
FROM seats s
JOIN seat_rows r ON r.row_id = s.row_id
JOIN sections sec ON sec.section_id = r.section_id
JOIN events e ON e.venue_id = sec.venue_id
LEFT JOIN booking_seats bs ON bs.seat_id = s.seat_id AND bs.event_id = e.event_id AND bs.status IN ('Held','Booked')
WHERE e.event_id = 1 AND bs.booking_seat_id IS NULL;

-- 4. Bookings by customer
SELECT b.*, e.title FROM bookings b JOIN events e ON e.event_id = b.event_id WHERE b.user_id = 3;

-- 5. Seats in a booking
SELECT bs.booking_id, sec.name, r.row_label, s.seat_number, bs.price
FROM booking_seats bs
JOIN seats s ON s.seat_id = bs.seat_id
JOIN seat_rows r ON r.row_id = s.row_id
JOIN sections sec ON sec.section_id = r.section_id;

-- 6. Payment status for bookings
SELECT b.booking_id, b.status AS booking_status, p.status AS payment_status, p.amount FROM bookings b JOIN payments p ON p.booking_id = b.booking_id;

-- 7. Tickets for an event
SELECT t.ticket_number, u.name, e.title FROM tickets t JOIN bookings b ON b.booking_id = t.booking_id JOIN users u ON u.user_id = b.user_id JOIN events e ON e.event_id = b.event_id;

-- 8. Revenue per event
SELECT * FROM vw_event_revenue ORDER BY revenue DESC;

-- 9. Daily bookings
SELECT DATE(booked_at) AS booking_date, COUNT(*) AS total_bookings FROM bookings GROUP BY DATE(booked_at);

-- 10. Monthly bookings
SELECT DATE_FORMAT(booked_at, '%Y-%m') AS month, COUNT(*) AS total_bookings FROM bookings GROUP BY DATE_FORMAT(booked_at, '%Y-%m');

-- 11. Popular events by booked seats
SELECT e.title, COUNT(bs.booking_seat_id) AS booked_seats FROM events e LEFT JOIN booking_seats bs ON bs.event_id = e.event_id AND bs.status = 'Booked' GROUP BY e.event_id ORDER BY booked_seats DESC;

-- 12. Available seats report
SELECT * FROM vw_available_seats;

-- 13. Refund report
SELECT r.*, e.title, u.name FROM refunds r JOIN bookings b ON b.booking_id = r.booking_id JOIN events e ON e.event_id = b.event_id JOIN users u ON u.user_id = b.user_id;

-- 14. Active users by role
SELECT ro.role_name, COUNT(*) AS users_count FROM users u JOIN roles ro ON ro.role_id = u.role_id WHERE u.is_active = 1 GROUP BY ro.role_name;

-- 15. Events by organizer
SELECT u.name AS organizer, COUNT(e.event_id) AS total_events FROM users u LEFT JOIN events e ON e.organizer_id = u.user_id GROUP BY u.user_id;

-- 16. Audit trail recent actions
SELECT a.*, u.email FROM audit_logs a LEFT JOIN users u ON u.user_id = a.user_id ORDER BY a.created_at DESC LIMIT 50;

-- 17. Validate one ticket
SELECT t.ticket_number, t.status, e.title, e.event_date FROM tickets t JOIN bookings b ON b.booking_id = t.booking_id JOIN events e ON e.event_id = b.event_id WHERE t.ticket_number = 'TKT-EXAMPLE';

-- 18. Failed payments
SELECT p.*, b.user_id FROM payments p JOIN bookings b ON b.booking_id = p.booking_id WHERE p.status = 'Failed';

-- 19. Venue capacity by section
SELECT v.name AS venue, sec.name AS section_name, COUNT(s.seat_id) AS seats FROM venues v JOIN sections sec ON sec.venue_id = v.venue_id JOIN seat_rows r ON r.section_id = sec.section_id JOIN seats s ON s.row_id = r.row_id GROUP BY v.venue_id, sec.section_id;

-- 20. Customers with confirmed bookings
SELECT DISTINCT u.user_id, u.name, u.email FROM users u JOIN bookings b ON b.user_id = u.user_id WHERE b.status = 'Confirmed';

-- 21. Cancelled booking seats
SELECT * FROM booking_seats WHERE status = 'Cancelled';

-- 22. Ticket scans by gate staff
SELECT q.*, u.name AS staff_name FROM qr_scans q JOIN users u ON u.user_id = q.scanned_by;
