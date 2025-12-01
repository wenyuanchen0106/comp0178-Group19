-- =========================================
-- Seed data only: INSERT / INSERT IGNORE
-- No DROP / DELETE / overwrite of existing data
-- =========================================

USE auction_db;

-- 1) Roles: buyer / seller
-- If a role_name already exists, UNIQUE + INSERT IGNORE will skip it without error
INSERT IGNORE INTO roles (role_name) VALUES 
('buyer'), 
('seller');


-- 2) Demo users: one buyer + one seller
-- Password for both is 123123 (bcrypt hash)
-- This only adds two extra rows, does not touch your existing users
INSERT IGNORE INTO users (name, email, password_hash, role_id)
VALUES
  (
    'Demo Buyer',
    'demo_buyer1@example.com',
    '$2b$10$rydOVX41S3da.uTyPVd9Xe7Zj52MgH1/eeoqemBG7vDdmBeFYrhh2', -- 123123
    (SELECT role_id FROM roles WHERE role_name = 'buyer' LIMIT 1)
  ),
  (
    'Demo Seller',
    'demo_seller1@example.com',
    '$2b$10$rydOVX41S3da.uTyPVd9Xe7Zj52MgH1/eeoqemBG7vDdmBeFYrhh2', -- 123123
    (SELECT role_id FROM roles WHERE role_name = 'seller' LIMIT 1)
  );

-- 3) Categories: basic examples
-- If you already inserted the same category_name, UNIQUE + INSERT IGNORE will skip it

INSERT IGNORE INTO categories (category_name) VALUES
  ('Books'),
  ('Electronics'),
  ('Clothing'),
  ('Home & Garden'),
  ('Sports'),
  ('Toys'),
  ('Fashion');
 

-- 4) Demo items: all owned by Demo Seller
-- Only insert rows; does not affect existing items created via create_auction
INSERT INTO items (title, description, category_id, seller_id)
VALUES
  (
    'Vintage Coffee Table',
    'Solid wood coffee table with some signs of wear but very sturdy.',
    (SELECT category_id FROM categories WHERE category_name = 'Home & Garden' LIMIT 1),
    (SELECT user_id FROM users WHERE email = 'demo_seller1@example.com' LIMIT 1)
  ),
  (
    'Football Boots Size 42',
    'Lightweight football boots, barely used, perfect for weekend games.',
    (SELECT category_id FROM categories WHERE category_name = 'Sports' LIMIT 1),
    (SELECT user_id FROM users WHERE email = 'demo_seller1@example.com' LIMIT 1)
  ),
  (
    'LEGO City Set',
    'Box opened but all pieces included, with instruction booklet.',
    (SELECT category_id FROM categories WHERE category_name = 'Toys' LIMIT 1),
    (SELECT user_id FROM users WHERE email = 'demo_seller1@example.com' LIMIT 1)
  );

-- 5) Create auctions for these demo items

-- (1) Vintage Coffee Table: ends in 7 days, active
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    20.00,       -- starting price
    35.00,       -- reserve price
    NOW(),
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    NULL,
    'active'
FROM items i
JOIN users u ON i.seller_id = u.user_id
WHERE i.title = 'Vintage Coffee Table'
  AND u.email = 'demo_seller1@example.com'
ORDER BY i.item_id DESC
LIMIT 1;

-- (2) Football Boots: ends in 3 days, active
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    15.00,
    NULL,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 3 DAY),
    NULL,
    'active'
FROM items i
JOIN users u ON i.seller_id = u.user_id
WHERE i.title = 'Football Boots Size 42'
  AND u.email = 'demo_seller1@example.com'
ORDER BY i.item_id DESC
LIMIT 1;

-- (3) LEGO City Set: auction ended yesterday, finished, winner = Demo Buyer
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    10.00,
    NULL,
    DATE_SUB(NOW(), INTERVAL 5 DAY),
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    (SELECT user_id FROM users WHERE email = 'demo_buyer1@example.com' LIMIT 1),
    'finished'
FROM items i
JOIN users u ON i.seller_id = u.user_id
WHERE i.title = 'LEGO City Set'
  AND u.email = 'demo_seller1@example.com'
ORDER BY i.item_id DESC
LIMIT 1;

-- 6) Add some bid history for these auctions
-- Note: column names are bid_amount / buyer_id, matching your schema

-- Vintage Coffee Table: two bids from Demo Buyer
INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
SELECT 
    a.auction_id,
    (SELECT user_id FROM users WHERE email = 'demo_buyer1@example.com' LIMIT 1),
    22.00,
    DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM auctions a
JOIN items i ON a.item_id = i.item_id
JOIN users u ON a.seller_id = u.user_id
WHERE i.title = 'Vintage Coffee Table'
  AND u.email = 'demo_seller1@example.com'
ORDER BY a.auction_id DESC
LIMIT 1;

INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
SELECT 
    a.auction_id,
    (SELECT user_id FROM users WHERE email = 'demo_buyer1@example.com' LIMIT 1),
    26.50,
    DATE_SUB(NOW(), INTERVAL 12 HOUR)
FROM auctions a
JOIN items i ON a.item_id = i.item_id
JOIN users u ON a.seller_id = u.user_id
WHERE i.title = 'Vintage Coffee Table'
  AND u.email = 'demo_seller1@example.com'
ORDER BY a.auction_id DESC
LIMIT 1;

-- Football Boots: one bid from Demo Buyer 1
INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
SELECT 
    a.auction_id,
    (SELECT user_id FROM users WHERE email = 'demo_buyer1@example.com' LIMIT 1),
    18.00,
    DATE_SUB(NOW(), INTERVAL 2 HOUR)
FROM auctions a
JOIN items i ON a.item_id = i.item_id
JOIN users u ON a.seller_id = u.user_id
WHERE i.title = 'Football Boots Size 42'
  AND u.email = 'demo_seller1@example.com'
ORDER BY a.auction_id DESC
LIMIT 1;

-- 7) Extra demo buyer: used to demonstrate “You lost” etc.
INSERT IGNORE INTO users (name, email, password_hash, role_id)
VALUES (
    'Demo Buyer 2',
    'demo_buyer2@example.com',
    '$2b$10$rydOVX41S3da.uTyPVd9Xe7Zj52MgH1/eeoqemBG7vDdmBeFYrhh2', -- 123123
    (SELECT role_id FROM roles WHERE role_name = 'buyer' LIMIT 1)
);

-- 8) More demo items, still owned by Demo Seller
INSERT INTO items (title, description, category_id, seller_id)
VALUES
  (
    'MacBook Pro 2019 13"',
    'Used MacBook Pro 13-inch 2019, 8GB RAM, 256GB SSD.',
    (SELECT category_id FROM categories WHERE category_name = 'Electronics' LIMIT 1),
    (SELECT user_id FROM users WHERE email = 'demo_seller1@example.com' LIMIT 1)
  ),
  (
    'Denim Jacket Size M',
    'Blue denim jacket, size M, good condition.',
    (SELECT category_id FROM categories WHERE category_name = 'Clothing' LIMIT 1),
    (SELECT user_id FROM users WHERE email = 'demo_seller1@example.com' LIMIT 1)
  ),
  (
    'Children Story Book Set',
    'Set of 10 children story books in good condition.',
    (SELECT category_id FROM categories WHERE category_name = 'Books' LIMIT 1),
    (SELECT user_id FROM users WHERE email = 'demo_seller1@example.com' LIMIT 1)
  );

-- 9) Create auctions for these new items

-- (4) MacBook Pro: ends in 2 days, active
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    500.00,       -- starting price
    650.00,       -- reserve price
    NOW(),
    DATE_ADD(NOW(), INTERVAL 2 DAY),
    NULL,
    'active'
FROM items i
JOIN users u ON i.seller_id = u.user_id
WHERE i.title = 'MacBook Pro 2019 13"'
  AND u.email = 'demo_seller1@example.com'
ORDER BY i.item_id DESC
LIMIT 1;

-- (5) Denim Jacket: ends in 5 days, active, no reserve price
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    15.00,       -- starting price
    NULL,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 5 DAY),
    NULL,
    'active'
FROM items i
JOIN users u ON i.seller_id = u.user_id
WHERE i.title = 'Denim Jacket Size M'
  AND u.email = 'demo_seller1@example.com'
ORDER BY i.item_id DESC
LIMIT 1;

-- (6) Children Story Book Set: finished auction, winner is Demo Buyer 2
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    5.00,        -- starting price
    NULL,
    DATE_SUB(NOW(), INTERVAL 4 DAY),
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    (SELECT user_id FROM users WHERE email = 'demo_buyer2@example.com' LIMIT 1),
    'finished'
FROM items i
JOIN users u ON i.seller_id = u.user_id
WHERE i.title = 'Children Story Book Set'
  AND u.email = 'demo_seller1@example.com'
ORDER BY i.item_id DESC
LIMIT 1;

-- 10) Add bid history for these new auctions

-- MacBook Pro: Demo Buyer 1 bids 520 then 580 (currently leading)
INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
SELECT 
    a.auction_id,
    (SELECT user_id FROM users WHERE email = 'demo_buyer1@example.com' LIMIT 1),
    520.00,
    DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM auctions a
JOIN items i ON a.item_id = i.item_id
JOIN users u ON a.seller_id = u.user_id
WHERE i.title = 'MacBook Pro 2019 13"'
  AND u.email = 'demo_seller1@example.com'
ORDER BY a.auction_id DESC
LIMIT 1;

INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
SELECT 
    a.auction_id,
    (SELECT user_id FROM users WHERE email = 'demo_buyer1@example.com' LIMIT 1),
    580.00,
    DATE_SUB(NOW(), INTERVAL 6 HOUR)
FROM auctions a
JOIN items i ON a.item_id = i.item_id
JOIN users u ON a.seller_id = u.user_id
WHERE i.title = 'MacBook Pro 2019 13"'
  AND u.email = 'demo_seller1@example.com'
ORDER BY a.auction_id DESC
LIMIT 1;

-- Children Story Book Set: Buyer1 bids 6.00, Buyer2 bids 7.50 (final winner is Buyer2)
INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
SELECT 
    a.auction_id,
    (SELECT user_id FROM users WHERE email = 'demo_buyer1@example.com' LIMIT 1),
    6.00,
    DATE_SUB(NOW(), INTERVAL 3 DAY)
FROM auctions a
JOIN items i ON a.item_id = i.item_id
JOIN users u ON a.seller_id = u.user_id
WHERE i.title = 'Children Story Book Set'
  AND u.email = 'demo_seller1@example.com'
ORDER BY a.auction_id DESC
LIMIT 1;

INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
SELECT 
    a.auction_id,
    (SELECT user_id FROM users WHERE email = 'demo_buyer2@example.com' LIMIT 1),
    7.50,
    DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM auctions a
JOIN items i ON a.item_id = i.item_id
JOIN users u ON a.seller_id = u.user_id
WHERE i.title = 'Children Story Book Set'
  AND u.email = 'demo_seller1@example.com'
ORDER BY a.auction_id DESC
LIMIT 1;

