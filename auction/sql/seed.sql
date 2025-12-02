USE auction_db;

SET FOREIGN_KEY_CHECKS = 0;

-- Reset all tables
TRUNCATE TABLE autobids;
TRUNCATE TABLE watchlist;
TRUNCATE TABLE favourites;
TRUNCATE TABLE recommendations;
TRUNCATE TABLE reports;
TRUNCATE TABLE payments;
TRUNCATE TABLE bids;
TRUNCATE TABLE auctions;
TRUNCATE TABLE items;
TRUNCATE TABLE categories;
TRUNCATE TABLE users;
TRUNCATE TABLE roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================
-- 1. Roles
-- ================================
INSERT INTO roles (role_name) VALUES
('buyer'),
('seller'),
('admin');

-- ================================
-- 2. Users (Marvel Characters)
-- ================================
-- ⚠️ NOTE: These users use SHA2 hashing for testing purposes.
-- If your login system uses PHP's password_verify(), these specific accounts
-- might not log in unless you register new ones or update the logic.
-- All passwords are: password123
INSERT INTO users (user_id, name, email, password_hash, role_id) VALUES
(1, 'Peter Parker',   'spidey@avengers.com', SHA2('password123', 256), 1), -- Buyer (Spider-Man)
(2, 'Steve Rogers',   'cap@avengers.com',    SHA2('password123', 256), 1), -- Buyer (Captain America)
(3, 'Tony Stark',     'tony@stark.com',      SHA2('password123', 256), 2), -- Seller (Iron Man)
(4, 'Nick Fury',      'fury@shield.gov',     SHA2('password123', 256), 2), -- Seller (Nick Fury)
(5, 'Thor Odinson',   'thor@asgard.com',     SHA2('password123', 256), 1), -- Buyer (Thor)
(6, 'Rocket Raccoon', 'rocket@guardians.gal',SHA2('password123', 256), 2); -- Seller (Rocket)

-- Note: To create an Admin account, please visit create_initial_admin.php

-- ================================
-- 3. Categories
-- ================================
INSERT INTO categories (category_name) VALUES
('Infinity Stones'),
('Weapons'),
('Armor & Tech'),
('Relics'),
('Vehicles');

-- ================================
-- 4. Items
-- ================================
-- Note: NULL image_paths will display the default placeholder.
INSERT INTO items (item_id, title, description, category_id, seller_id, image_path) VALUES
-- Items with images
(1, 'The Infinity Gauntlet', 'Designed by Eitri, capable of harnessing the power of the Infinity Stones. Slightly used. Snap at your own risk.', 1, 3, 'infinity_gauntlet.jpg'),
(2, 'Vibranium Shield', 'Prototype circular shield made of pure Vibranium. Absorbs all kinetic energy. Returned by Howard Stark.', 2, 3, 'cap_shield.jpg'),
(3, 'Mjolnir (Hammer)', 'Forged in the heart of a dying star. Only the worthy may lift it. Great for generating lightning.', 2, 4, 'mjolnir.jpg'),
(4, 'Mark 85 Helmet', 'Nano-tech helmet from the latest Iron Man suit. Features HUD, life support, and Jarvis integration.', 3, 3, 'ironman_helmet.jpg'),
(5, 'The Tesseract', 'Containment vessel for the Space Stone. Provides unlimited renewable energy. Handle with care.', 1, 4, 'tesseract.jpg'),

-- Items without images (placeholders)
(6, 'Web Shooters (Pair)', 'Daily Bugle proprietary tech. Fluid cartridges not included. Warning: webbing dissolves after 2 hours.', 3, 1, NULL),
(7, 'Pym Particles (Vial)', 'Subatomic particles capable of reducing or increasing mass/scale. Do not ingest.', 4, 6, NULL),
(8, 'Groot''s Twig', 'A small twig from a Flora colossus. Might grow into a tree if planted.', 4, 6, NULL),
(9, 'Quinjet Blueprint', 'Classified schematics for the Avengers Quinjet stealth transport.', 5, 4, NULL);

-- ================================
-- 5. Auctions
-- ================================
INSERT INTO auctions (auction_id, item_id, seller_id, start_price, reserve_price, start_date, end_date, winner_id, status) VALUES
-- 1. Infinity Gauntlet: Active
(1, 1, 3, 500000.00, 1000000.00, NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), NULL, 'active'),

-- 2. Cap's Shield: Active
(2, 2, 3, 5000.00,    8000.00,    NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), NULL, 'active'),

-- 3. Mjolnir: Finished (Sold to Cap)
(3, 3, 4, 1000.00,    2000.00,    DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 'finished'),

-- 4. Iron Man Helmet: Just Started
(4, 4, 3, 15000.00,   20000.00,   NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY), NULL, 'active'),

-- 5. Tesseract: Finished (Unsold/Reserve not met)
(5, 5, 4, 999999.00, 999999.00,   DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, 'finished'),

-- 6. Web Shooters: Active
(6, 6, 1, 100.00,     200.00,     NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), NULL, 'active');

-- ================================
-- 6. Bids
-- ================================
INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time) VALUES
-- Gauntlet Bidding War (Thor vs Rocket)
(1, 5, 510000.00, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(1, 6, 550000.00, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 5, 600000.00, DATE_SUB(NOW(), INTERVAL 1 HOUR)), -- Thor is leading

-- Shield (Peter Parker interested, Cap buying it back)
(2, 1, 5200.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 2, 6000.00, DATE_SUB(NOW(), INTERVAL 5 HOUR)), -- Cap winning

-- Mjolnir (Cap won)
(3, 2, 1200.00, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 5, 1500.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 3000.00, DATE_SUB(NOW(), INTERVAL 1 DAY)); -- Winning bid

-- ================================
-- 7. Watchlist
-- ================================
INSERT INTO watchlist (user_id, auction_id) VALUES
(1, 2), -- Peter watching Shield
(1, 4), -- Peter watching Helmet
(2, 3), -- Steve watching Hammer
(6, 1); -- Rocket watching Gauntlet

-- ================================
-- 8. Favourites
-- ================================
INSERT INTO favourites (user_id, item_id) VALUES
(1, 2),
(1, 4),
(6, 1);

-- ================================
-- 9. Recommendations
-- ================================
INSERT INTO recommendations (user_id, item_id, reason, score) VALUES
(1, 4, 'Because you like Stark Tech', 95.5),
(1, 6, 'Similar to your purchases', 88.0),
(2, 2, 'You might need a new shield', 99.9);

-- ================================
-- 10. Autobids
-- ================================
INSERT INTO autobids (user_id, auction_id, max_amount, step) VALUES
(6, 1, 800000.00, 1000.00); -- Rocket set autobid for Gauntlet

-- ================================
-- 11. Reports
-- ================================
INSERT INTO reports (user_id, auction_id, item_id, description, status) VALUES
(2, 5, 5, 'This item is too dangerous for civilians.', 'open'),
(5, 1, 1, 'This belongs in a museum in Asgard.', 'closed');

-- ================================
-- 12. Payments
-- ================================
INSERT INTO payments (user_id, auction_id, amount, payment_method, status, paid_at) VALUES
-- Steve Rogers paid for Mjolnir
(2, 3, 3000.00, 'Stark Industries Credit', 'completed', NOW());

-- ========================================
-- Data Initialization Complete!
-- ========================================
--
-- POST-INSTALLATION GUIDE
-- ========================================
--
-- 1. Password Handling:
--    The users inserted above use SHA2 hashing. If your application uses
--    PHP's password_verify(), create a new account via the Register page
--    to test logging in.
--
-- 2. Creating an Admin:
--    Admin passwords require PHP's password_hash() and cannot be set directly via SQL.
--    Please navigate to: http://localhost/auction/create_initial_admin.php
--    Default Admin credentials will be: admin@auction.com / password123
--    (Remember to delete that file after use).
--
-- 3. Admin Features:
--    Once logged in as Admin, you can access the "Admin Panel" to:
--    - Manage Reports (admin_reports.php)
--    - Remove inappropriate auctions
--    - Manage Admin accounts (manage_admins.php)
-- ========================================
