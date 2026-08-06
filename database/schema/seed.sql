USE event_ticketing_db;

INSERT INTO roles(role_name) VALUES
('Administrator'), ('Organizer'), ('Customer'), ('Gate Staff');

INSERT INTO users(role_id, name, email, password_hash, phone) VALUES
(1, 'Admin User', 'admin@example.com', 'RUN_PUBLIC_SETUP_TO_GENERATE_HASH', '9000000001'),
(2, 'Demo Organizer', 'organizer@example.com', 'RUN_PUBLIC_SETUP_TO_GENERATE_HASH', '9000000002'),
(3, 'Demo Customer', 'customer@example.com', 'RUN_PUBLIC_SETUP_TO_GENERATE_HASH', '9000000003'),
(4, 'Gate Staff', 'gate@example.com', 'RUN_PUBLIC_SETUP_TO_GENERATE_HASH', '9000000004');

INSERT INTO venues(name, address, city, capacity) VALUES
('City Convention Hall', 'MG Road', 'Pune', 60),
('Open Air Arena', 'Lake Road', 'Mumbai', 80);

INSERT INTO sections(venue_id, name) VALUES
(1, 'Gold'), (1, 'Silver'), (2, 'Premium'), (2, 'General');

INSERT INTO seat_rows(section_id, row_label) VALUES
(1, 'A'), (1, 'B'), (2, 'C'), (2, 'D'), (3, 'A'), (3, 'B'), (4, 'C'), (4, 'D');

INSERT INTO seats(row_id, seat_number)
SELECT row_id, n
FROM seat_rows
CROSS JOIN (
    SELECT '1' n UNION SELECT '2' UNION SELECT '3' UNION SELECT '4' UNION SELECT '5'
    UNION SELECT '6' UNION SELECT '7' UNION SELECT '8' UNION SELECT '9' UNION SELECT '10'
) nums;

INSERT INTO events(organizer_id, venue_id, title, description, event_date, status, poster_image) VALUES
(2, 1, 'Dhurandhar', 'A high-energy Hindi action thriller headlined by Ranveer Singh with an intense ensemble cast.', DATE_ADD(NOW(), INTERVAL 7 DAY), 'Published', 'https://www.impawards.com/intl/india/2025/posters/dhurandhar.jpg'),
(2, 2, 'Spider-Man: Brand New Day', 'Peter Parker swings back into action for a brand-new Spider-Man adventure.', DATE_ADD(NOW(), INTERVAL 12 DAY), 'Published', 'https://www.sonypictures.com/sites/default/files/styles/max_860x460/public/title-key-art/spidermanbrandnewday_onesheet_1400x2100.jpg?itok=Nh6VAAh-'),
(2, 1, 'Avatar: Fire and Ash', 'James Cameron returns to Pandora with a spectacular new chapter in the Avatar saga.', DATE_ADD(NOW(), INTERVAL 18 DAY), 'Published', 'https://www.impawards.com/2025/posters/avatar_fire_and_ash.jpg'),
(2, 2, 'Avengers: Doomsday', 'Earth''s mightiest heroes face a massive new Marvel threat on the biggest screen.', DATE_ADD(NOW(), INTERVAL 24 DAY), 'Published', 'https://www.impawards.com/2026/posters/avengers_doomsday.jpg'),
(2, 1, 'The Odyssey', 'A mythic big-screen journey inspired by the legendary Greek epic.', DATE_ADD(NOW(), INTERVAL 31 DAY), 'Published', 'https://www.impawards.com/2026/posters/odyssey_ver4.jpg'),
(2, 2, 'Toy Story 5', 'Woody, Buzz, and friends return for another family-friendly animated adventure.', DATE_ADD(NOW(), INTERVAL 38 DAY), 'Published', 'https://www.impawards.com/2026/posters/toy_story_five.jpg');

INSERT INTO ticket_types(event_id, section_id, type_name, price) VALUES
(1, 1, 'Gold Pass', 999.00),
(1, 2, 'Silver Pass', 599.00),
(2, 3, 'Premium Pass', 1299.00),
(2, 4, 'General Pass', 799.00),
(3, 1, 'Gold Pass', 899.00),
(3, 2, 'Silver Pass', 599.00),
(4, 3, 'Premium Pass', 1299.00),
(4, 4, 'General Pass', 799.00),
(5, 1, 'Gold Pass', 999.00),
(5, 2, 'Silver Pass', 699.00),
(6, 3, 'Premium Pass', 1199.00),
(6, 4, 'General Pass', 749.00);

INSERT INTO audit_logs(user_id, action, table_name, details) VALUES
(1, 'SEED_DATA_IMPORTED', 'database', 'Initial roles, users, venues, seats, events, and ticket types inserted.');
