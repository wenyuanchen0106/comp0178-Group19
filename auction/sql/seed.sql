USE auction_db;

SET FOREIGN_KEY_CHECKS = 0;

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
-- 2. Users (漫威角色)
-- ================================
-- ⚠️ 注意：这些用户使用SHA2哈希，仅用于测试
-- 如果你的登录系统使用password_verify()，这些账号将无法登录
-- 请通过注册功能创建新账号，或使用 create_initial_admin.php 创建管理员
-- 密码全部是: password123
INSERT INTO users (user_id, name, email, password_hash, role_id) VALUES
(1, 'Peter Parker',   'spidey@avengers.com', SHA2('password123', 256), 1), -- 买家 (蜘蛛侠)
(2, 'Steve Rogers',   'cap@avengers.com',    SHA2('password123', 256), 1), -- 买家 (美队)
(3, 'Tony Stark',     'tony@stark.com',      SHA2('password123', 256), 2), -- 卖家 (钢铁侠)
(4, 'Nick Fury',      'fury@shield.gov',     SHA2('password123', 256), 2), -- 卖家 (神盾局长)
(5, 'Thor Odinson',   'thor@asgard.com',     SHA2('password123', 256), 1), -- 买家 (雷神)
(6, 'Rocket Raccoon', 'rocket@guardians.gal',SHA2('password123', 256), 2); -- 卖家 (火箭浣熊)

-- 注意：管理员账号请访问 create_initial_admin.php 创建

-- ================================
-- 3. Categories (超级英雄分类)
-- ================================
INSERT INTO categories (category_name) VALUES
('Infinity Stones'),
('Weapons'),
('Armor & Tech'),
('Relics'),
('Vehicles');

-- ================================
-- 4. Items (核心：漫威装备)
-- ================================
-- 注意：image_path 对应你下载的图片。NULL 的会自动显示斯塔克占位符。
INSERT INTO items (item_id, title, description, category_id, seller_id, image_path) VALUES
-- 带图片的商品 (5个)
(1, 'The Infinity Gauntlet', 'Designed by Eitri, capable of harnessing the power of the Infinity Stones. Slightly used. Snap at your own risk.', 1, 3, 'infinity_gauntlet.jpg'),
(2, 'Vibranium Shield', 'Prototype circular shield made of pure Vibranium. Absorbs all kinetic energy. Returned by Howard Stark.', 2, 3, 'cap_shield.jpg'),
(3, 'Mjolnir (Hammer)', 'Forged in the heart of a dying star. Only the worthy may lift it. Great for generating lightning.', 2, 4, 'mjolnir.jpg'),
(4, 'Mark 85 Helmet', 'Nano-tech helmet from the latest Iron Man suit. Features HUD, life support, and Jarvis integration.', 3, 3, 'ironman_helmet.jpg'),
(5, 'The Tesseract', 'Containment vessel for the Space Stone. Provides unlimited renewable energy. Handle with care.', 1, 4, 'tesseract.jpg'),

-- 无图商品 (测试占位符)
(6, 'Web Shooters (Pair)', 'Daily Bugle proprietary tech. Fluid cartridges not included. Warning: webbing dissolves after 2 hours.', 3, 1, NULL),
(7, 'Pym Particles (Vial)', 'Subatomic particles capable of reducing or increasing mass/scale. Do not ingest.', 4, 6, NULL),
(8, 'Groot\'s Twig', 'A small twig from a Flora colossus. Might grow into a tree if planted.', 4, 6, NULL),
(9, 'Quinjet Blueprint', 'Classified schematics for the Avengers Quinjet stealth transport.', 5, 4, NULL);

-- ================================
-- 5. Auctions (拍卖状态)
-- ================================
INSERT INTO auctions (auction_id, item_id, seller_id, start_price, reserve_price, start_date, end_date, winner_id, status) VALUES
-- 1. 无限手套: 正在热拍 (Active)
(1, 1, 3, 500000.00, 1000000.00, NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), NULL, 'active'),

-- 2. 美队盾牌: 正在热拍 (Active)
(2, 2, 3, 5000.00,   8000.00,    NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), NULL, 'active'),

-- 3. 雷神之锤: 已结束，卖给美队 (Finished - Sold)
(3, 3, 4, 1000.00,   2000.00,    DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 'finished'),

-- 4. 钢铁侠头盔: 刚刚开始 (Active)
(4, 4, 3, 15000.00,  20000.00,   NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY), NULL, 'active'),

-- 5. 宇宙魔方: 已结束，流拍 (Finished - Unsold)
(5, 5, 4, 999999.00, 999999.00,  DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, 'finished'),

-- 6. 蛛网发射器: 正在热拍 (Active)
(6, 6, 1, 100.00,    200.00,     NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), NULL, 'active');

-- ================================
-- 6. Bids (出价记录)
-- ================================
INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time) VALUES
-- 无限手套的竞价战 (Thor vs Rocket)
(1, 5, 510000.00, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(1, 6, 550000.00, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 5, 600000.00, DATE_SUB(NOW(), INTERVAL 1 HOUR)), -- Thor 目前领先

-- 美队盾牌 (Peter Parker 想买)
(2, 1, 5200.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 2, 6000.00, DATE_SUB(NOW(), INTERVAL 5 HOUR)), -- 美队自己出价买回盾牌

-- 雷神之锤 (美队赢了)
(3, 2, 1200.00, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 5, 1500.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 3000.00, DATE_SUB(NOW(), INTERVAL 1 DAY)); -- 美队赢了

-- ================================
-- 7. Watchlist (关注列表)
-- ================================
INSERT INTO watchlist (user_id, auction_id) VALUES
(1, 2), -- Peter Parker 关注盾牌
(1, 4), -- Peter Parker 关注钢铁侠头盔
(2, 3), -- Steve Rogers 关注锤子
(6, 1); -- Rocket 关注无限手套

-- ================================
-- 8. Favourites (收藏夹)
-- ================================
INSERT INTO favourites (user_id, item_id) VALUES
(1, 2),
(1, 4),
(6, 1);

-- ================================
-- 9. Recommendations (推荐算法数据)
-- ================================
INSERT INTO recommendations (user_id, item_id, reason, score) VALUES
(1, 4, 'Because you like Stark Tech', 95.5),
(1, 6, 'Similar to your purchases', 88.0),
(2, 2, 'You might need a new shield', 99.9);

-- ================================
-- 10. Autobids (自动出价)
-- ================================
INSERT INTO autobids (user_id, auction_id, max_amount, step) VALUES
(6, 1, 800000.00, 1000.00); -- Rocket 设置了自动出价买手套

-- ================================
-- 11. Reports (举报数据)
-- ================================
INSERT INTO reports (user_id, auction_id, item_id, description, status) VALUES
(2, 5, 5, 'This item is too dangerous for civilians.', 'open'),
(5, 1, 1, 'This belongs in a museum in Asgard.', 'closed');

-- ================================
-- 12. Payments (支付记录)
-- ================================
INSERT INTO payments (user_id, auction_id, amount, payment_method, status, paid_at) VALUES
-- Steve Rogers 支付了雷神之锤
(2, 3, 3000.00, 'Stark Industries Credit', 'completed', NOW());

-- ========================================
-- 数据初始化完成！
-- ========================================
--
-- ⚠️ 重要提醒：测试用户密码问题
-- ========================================
-- 上面插入的测试用户（user_id 1-6）使用 SHA2() 哈希
-- 如果你的登录系统使用 password_verify()，这些账号将无法登录
--
-- 解决方案：
-- 1. 通过网站注册功能创建新的买家/卖家账号
-- 2. 或者修改 login_result.php 使用 SHA2 验证（不推荐）
--
-- ========================================
-- 创建管理员账号
-- ========================================
--
-- 由于管理员密码需要使用 PHP 的 password_hash() 函数，
-- 无法在 SQL 中直接创建。请按以下步骤操作：
--
-- 方法1（推荐）：使用自动创建脚本
-- ----------------------------------------
-- 1. 访问: http://localhost/auction/create_initial_admin.php
-- 2. 自动创建管理员账号:
--    邮箱: admin@auction.com
--    密码: password123
-- 3. 创建成功后立即删除该文件（安全考虑）
--
-- 方法2：手动在数据库中修改
-- ----------------------------------------
-- 1. 先通过注册功能创建一个普通账号
-- 2. 在 phpMyAdmin 中找到该用户记录
-- 3. 将 role_id 改为 3
-- 4. 重新登录即可获得管理员权限
--
-- 方法3：使用 manage_admins.php（需要先有管理员）
-- ----------------------------------------
-- 1. 先用方法1或方法2创建第一个管理员
-- 2. 登录后访问 manage_admins.php
-- 3. 可以创建更多管理员账号
--
-- ========================================
-- 管理员功能说明
-- ========================================
--
-- 管理员登录后可以：
-- 1. 查看所有用户举报 (admin_reports.php)
-- 2. 下架违规拍品 (将 status 改为 'removed')
-- 3. 标记举报为已解决
-- 4. 创建新的管理员账号 (manage_admins.php)
--
-- 管理员菜单会自动显示在导航栏：
-- - Reports: 查看和处理举报
-- - Admins: 管理管理员账号
-- - Admin Panel: 管理员主页
--
-- ========================================