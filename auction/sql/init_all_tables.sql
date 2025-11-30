-- =========================================
-- 只“增加”数据，不 DROP / 不 DELETE / 不覆盖现有数据
-- 适用于表结构
-- =========================================

USE auction_db;

-- 1) 角色：buyer / seller
-- 如果已经存在同名 role_name，会被 UNIQUE 拦住，INSERT IGNORE 不会报错
INSERT IGNORE INTO roles (role_name) VALUES 
('buyer'), 
('seller');


-- 2) Demo 用户：一个买家 + 一个卖家
-- 密码都是 123123（bcrypt 哈希），不会动你现有 users，只是多两行
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

-- 3) 分类：基础几类
-- 如果你之前已经插过同名 category_name，UNIQUE + INSERT IGNORE 会跳过，不会覆盖

    

INSERT IGNORE INTO categories (category_name) VALUES
  ('Books'),
  ('Electronics'),
  ('Clothing'),
  ('Home & Garden'),
  ('Sports'),
  ('Toys'),
  ('Fashion');
 

-- 4) Demo 商品（items）：都归 Demo Seller 所有
-- 这里只是插入，不会影响你之前 create_auction 插入的 item
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

-- 5) 为这些 Demo 商品创建拍卖（auctions）
-- ① Vintage Coffee Table：7 天后结束，active
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    20.00,       -- 起拍价
    35.00,       -- 保留价
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

-- ② Football Boots：3 天后结束，active
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

-- ③ LEGO City Set：昨天结束，finished，winner = Demo Buyer
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

-- 6) 为部分拍卖加一些历史出价（bids）
-- 注意列名是 bid_amount / buyer_id，对应你现在的表结构

-- 给 Vintage Coffee Table 加两口价
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

-- 给 Football Boots 加一口价
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

-- 7) 额外 Demo 买家：用于演示“出价失败 / You lost”等情况
INSERT IGNORE INTO users (name, email, password_hash, role_id)
VALUES (
    'Demo Buyer 2',
    'demo_buyer2@example.com',
    '$2b$10$rydOVX41S3da.uTyPVd9Xe7Zj52MgH1/eeoqemBG7vDdmBeFYrhh2', -- 123123
    (SELECT role_id FROM roles WHERE role_name = 'buyer' LIMIT 1)
);

-- 8) 更多 Demo 商品（items），仍然归 Demo Seller 所有
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

-- 9) 为这些新商品创建拍卖（auctions）

-- ⑦ MacBook Pro：2 天后结束，active
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    500.00,       -- 起拍价
    650.00,       -- 保留价
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

-- ⑧ Denim Jacket：5 天后结束，active，无保留价
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    15.00,       -- 起拍价
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

-- ⑨ Children Story Book Set：已结束的拍卖，winner 是 Demo Buyer 2
INSERT INTO auctions (
    item_id, seller_id, start_price, reserve_price,
    start_date, end_date, winner_id, status
)
SELECT 
    i.item_id,
    i.seller_id,
    5.00,        -- 起拍价
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

-- 10) 为这些新拍卖增加历史出价（bids）

-- MacBook Pro：Demo Buyer 1 出 520，再出 580（当前领先）
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

-- Children Story Book Set：Buyer1 出 6.00，Buyer2 出 7.50（最后赢家是 Buyer2）
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

