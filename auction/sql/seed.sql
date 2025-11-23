SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE roles;
TRUNCATE TABLE users;
TRUNCATE TABLE categories;
TRUNCATE TABLE items;
TRUNCATE TABLE auctions;
TRUNCATE TABLE bids;
TRUNCATE TABLE watchlist;
TRUNCATE TABLE recommendations;
TRUNCATE TABLE favourites;
TRUNCATE TABLE autobids;
TRUNCATE TABLE reporting;
TRUNCATE TABLE payments;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================
-- 1. Roles
-- ================================
INSERT INTO roles (role_name) VALUES
('buyer'),
('seller');

-- ================================
-- 2. Users
-- ================================
INSERT INTO users (name, email, password_hash, role_id) VALUES
('Alice Buyer', 'alice@example.com', SHA2('password123', 256), 1),
('Bob Buyer', 'bob@example.com', SHA2('password123', 256), 1),
('Charlie Seller', 'charlie@example.com', SHA2('password123', 256), 2),
('David Seller', 'david@example.com', SHA2('password123', 256), 2);

-- ================================
-- 3. Categories
-- ================================
INSERT INTO categories (category_name) VALUES
('Sports'),
('Electronics'),
('Fashion');

-- ================================
-- 4. Items
-- ================================
INSERT INTO items (title, description, category_id, seller_id) VALUES
('Basketball Shoes Size 44', 'High quality basketball shoes', 1, 3),
('Tennis Racket Pro', 'Professional tennis racket', 1, 3),
('Wireless Headphones', 'Noise cancelling headphones', 2, 4),
('Smartwatch X', 'Latest generation smartwatch', 2, 4);

-- ================================
-- 5. Auctions
-- ================================
INSERT INTO auctions (item_id, seller_id, start_price, reserve_price, start_date, end_date, winner_id, status) VALUES
(1, 3, 50, 70, NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), NULL, 'active'),
(2, 3, 80, 100, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), NULL, 'active'),
(3, 4, 120, 150, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NULL, 'active'),
(4, 4, 200, 250, NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), NULL, 'active');

-- ================================
-- 6. Bids
-- ================================
INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time) VALUES
(1, 1, 55, NOW()),
(1, 2, 60, NOW()),
(2, 1, 85, NOW()),
(3, 2, 130, NOW()),
(4, 1, 210, NOW());

-- ================================
-- 7. Watchlist
-- ================================
INSERT INTO watchlist (user_id, item_id) VALUES
(1, 1),
(1, 3),
(2, 2),
(2, 4);

-- ================================
-- 8. Favourite Items
-- ================================
INSERT INTO favourites (user_id, item_id, datetime) VALUES
(1, 1, NOW()),
(1, 2, NOW()),
(2, 3, NOW());

-- ================================
-- 9. Recommendations
-- ================================
INSERT INTO recommendations (user_id, item_id, reason, score) VALUES
(1, 1, 'Based on recent bid', 90),
(1, 2, 'Similar to watched items', 80),
(2, 3, 'Recommended category', 75);

-- ================================
-- 10. Autobid
-- ================================
INSERT INTO autobids (auction_id, user_id, max_amount, step) VALUES
(1, 1, 100, 5),
(3, 2, 180, 10);

-- ================================
-- 11. Reporting
-- ================================
INSERT INTO reporting (user_id, auction_id, item_id, description, status, created_at) VALUES
(1, 1, 1, 'Incorrect item description', 'open', NOW()),
(2, 3, 3, 'Potential scam', 'open', NOW());

-- ================================
-- 12. Payments
-- ================================
INSERT INTO payments (user_id, auction_id, amount, payment_method, status, datetime) VALUES
(1, 1, 60, 'Credit Card', 'completed', NOW()),
(2, 3, 130, 'PayPal', 'completed', NOW());

-- End of seed.sql

