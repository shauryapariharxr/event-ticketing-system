CREATE DATABASE IF NOT EXISTS event_ticketing_db;
USE event_ticketing_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, qr_scans, refunds, payments, tickets, booking_seats, bookings, ticket_types, events, seats, seat_rows, sections, venues, users, roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE venues (
    venue_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(80) NOT NULL,
    capacity INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CHECK (capacity > 0)
);

CREATE TABLE sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    name VARCHAR(80) NOT NULL,
    UNIQUE (venue_id, name),
    FOREIGN KEY (venue_id) REFERENCES venues(venue_id) ON DELETE CASCADE
);

CREATE TABLE seat_rows (
    row_id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    row_label VARCHAR(20) NOT NULL,
    UNIQUE (section_id, row_label),
    FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE CASCADE
);

CREATE TABLE seats (
    seat_id INT AUTO_INCREMENT PRIMARY KEY,
    row_id INT NOT NULL,
    seat_number VARCHAR(20) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE (row_id, seat_number),
    FOREIGN KEY (row_id) REFERENCES seat_rows(row_id) ON DELETE CASCADE
);

CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    venue_id INT NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    status ENUM('Draft','Published','Cancelled','Completed') NOT NULL DEFAULT 'Draft',
    poster_image VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(user_id),
    FOREIGN KEY (venue_id) REFERENCES venues(venue_id)
);

CREATE TABLE ticket_types (
    ticket_type_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    section_id INT NOT NULL,
    type_name VARCHAR(80) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    UNIQUE (event_id, section_id, type_name),
    CHECK (price >= 0),
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(section_id)
);

CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('Pending','Confirmed','Cancelled','Refunded') NOT NULL DEFAULT 'Pending',
    booked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id),
    CHECK (total_amount >= 0)
);

CREATE TABLE booking_seats (
    booking_seat_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    event_id INT NOT NULL,
    seat_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('Held','Booked','Cancelled','Refunded') NOT NULL DEFAULT 'Held',
    INDEX idx_booking_seat_status (event_id, seat_id, status),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id),
    FOREIGN KEY (seat_id) REFERENCES seats(seat_id),
    CHECK (price >= 0)
);

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('Mock Card','Mock UPI','Cash') NOT NULL DEFAULT 'Mock Card',
    status ENUM('Pending','Success','Failed','Refunded') NOT NULL DEFAULT 'Pending',
    transaction_ref VARCHAR(80) UNIQUE,
    paid_at DATETIME,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    CHECK (amount >= 0)
);

CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    booking_seat_id INT NOT NULL UNIQUE,
    ticket_number VARCHAR(40) NOT NULL UNIQUE,
    qr_code VARCHAR(255),
    issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Valid','Cancelled','Used') NOT NULL DEFAULT 'Valid',
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_seat_id) REFERENCES booking_seats(booking_seat_id) ON DELETE CASCADE
);

CREATE TABLE refunds (
    refund_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255),
    status ENUM('Requested','Approved','Rejected','Processed') NOT NULL DEFAULT 'Requested',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id),
    CHECK (amount >= 0)
);

CREATE TABLE qr_scans (
    scan_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    scanned_by INT NOT NULL,
    scanned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    result ENUM('Accepted','Rejected') NOT NULL,
    notes VARCHAR(255),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (scanned_by) REFERENCES users(user_id)
);

CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(80),
    record_id INT,
    details TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE INDEX idx_events_date_status ON events(event_date, status);
CREATE INDEX idx_booking_user ON bookings(user_id, booked_at);
CREATE INDEX idx_booking_seats_event ON booking_seats(event_id, seat_id);
CREATE INDEX idx_payments_status ON payments(status);

CREATE OR REPLACE VIEW vw_event_revenue AS
SELECT e.event_id, e.title, COALESCE(SUM(p.amount), 0) AS revenue, COUNT(DISTINCT b.booking_id) AS paid_bookings
FROM events e
LEFT JOIN bookings b ON b.event_id = e.event_id AND b.status IN ('Confirmed','Refunded')
LEFT JOIN payments p ON p.booking_id = b.booking_id AND p.status IN ('Success','Refunded')
GROUP BY e.event_id, e.title;

CREATE OR REPLACE VIEW vw_available_seats AS
SELECT e.event_id, e.title, COUNT(s.seat_id) AS available_seats
FROM events e
JOIN sections sec ON sec.venue_id = e.venue_id
JOIN seat_rows sr ON sr.section_id = sec.section_id
JOIN seats s ON s.row_id = sr.row_id AND s.is_active = 1
LEFT JOIN booking_seats bs ON bs.event_id = e.event_id AND bs.seat_id = s.seat_id AND bs.status IN ('Held','Booked')
WHERE bs.booking_seat_id IS NULL
GROUP BY e.event_id, e.title;

DELIMITER //

CREATE TRIGGER trg_booking_seats_before_insert
BEFORE INSERT ON booking_seats
FOR EACH ROW
BEGIN
    IF NEW.status IN ('Held','Booked') AND EXISTS (
        SELECT 1 FROM booking_seats
        WHERE event_id = NEW.event_id AND seat_id = NEW.seat_id AND status IN ('Held','Booked')
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Seat already held or booked for this event.';
    END IF;
END//

CREATE TRIGGER trg_booking_seats_before_update
BEFORE UPDATE ON booking_seats
FOR EACH ROW
BEGIN
    IF NEW.status IN ('Held','Booked') AND EXISTS (
        SELECT 1 FROM booking_seats
        WHERE event_id = NEW.event_id
          AND seat_id = NEW.seat_id
          AND booking_seat_id <> NEW.booking_seat_id
          AND status IN ('Held','Booked')
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Seat already held or booked for this event.';
    END IF;
END//

CREATE TRIGGER trg_payment_success_confirm
AFTER UPDATE ON payments
FOR EACH ROW
BEGIN
    IF NEW.status = 'Success' AND OLD.status <> 'Success' THEN
        UPDATE bookings SET status = 'Confirmed' WHERE booking_id = NEW.booking_id;
        UPDATE booking_seats SET status = 'Booked' WHERE booking_id = NEW.booking_id;
    END IF;
END//

CREATE TRIGGER trg_booking_cancel_release
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    IF NEW.status IN ('Cancelled','Refunded') AND OLD.status <> NEW.status THEN
        UPDATE booking_seats SET status = NEW.status WHERE booking_id = NEW.booking_id;
        UPDATE tickets SET status = 'Cancelled' WHERE booking_id = NEW.booking_id;
    END IF;
END//

CREATE PROCEDURE sp_create_booking(
    IN p_user_id INT,
    IN p_event_id INT,
    IN p_seat_ids TEXT,
    OUT p_booking_id INT
)
BEGIN
    DECLARE v_total DECIMAL(10,2) DEFAULT 0;
    DECLARE v_seat_id INT;
    DECLARE v_pos INT DEFAULT 1;
    DECLARE v_next INT;
    DECLARE v_token VARCHAR(20);
    DECLARE v_price DECIMAL(10,2);

    START TRANSACTION;

    INSERT INTO bookings(user_id, event_id, status) VALUES (p_user_id, p_event_id, 'Pending');
    SET p_booking_id = LAST_INSERT_ID();

    seat_loop: LOOP
        SET v_next = LOCATE(',', p_seat_ids, v_pos);
        IF v_next = 0 THEN
            SET v_token = TRIM(SUBSTRING(p_seat_ids, v_pos));
        ELSE
            SET v_token = TRIM(SUBSTRING(p_seat_ids, v_pos, v_next - v_pos));
        END IF;

        IF v_token <> '' THEN
            SET v_seat_id = CAST(v_token AS UNSIGNED);

            IF EXISTS (
                SELECT 1 FROM booking_seats
                WHERE event_id = p_event_id AND seat_id = v_seat_id AND status IN ('Held','Booked')
            ) THEN
                ROLLBACK;
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'One or more selected seats are no longer available.';
            END IF;

            SELECT tt.price INTO v_price
            FROM seats s
            JOIN seat_rows r ON r.row_id = s.row_id
            JOIN sections sec ON sec.section_id = r.section_id
            JOIN ticket_types tt ON tt.section_id = sec.section_id AND tt.event_id = p_event_id
            WHERE s.seat_id = v_seat_id
            LIMIT 1;

            INSERT INTO booking_seats(booking_id, event_id, seat_id, price, status)
            VALUES (p_booking_id, p_event_id, v_seat_id, v_price, 'Held');

            SET v_total = v_total + v_price;
        END IF;

        IF v_next = 0 THEN
            LEAVE seat_loop;
        END IF;
        SET v_pos = v_next + 1;
    END LOOP;

    UPDATE bookings SET total_amount = v_total WHERE booking_id = p_booking_id;
    INSERT INTO payments(booking_id, amount, status, transaction_ref)
    VALUES (p_booking_id, v_total, 'Pending', CONCAT('MOCK-', UUID_SHORT()));

    COMMIT;
END//

DELIMITER ;
