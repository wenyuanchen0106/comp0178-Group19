-- ========================================
-- 拍卖系统数据库结构
-- ========================================
-- 使用方法：
-- 1. 打开 xampp 后浏览器进入 http://localhost/phpmyadmin
-- 2. 创建数据库：
--    CREATE DATABASE IF NOT EXISTS auction_db
--    DEFAULT CHARACTER SET utf8mb4
--    DEFAULT COLLATE utf8mb4_unicode_ci;
-- 3. 选择 auction_db 数据库
-- 4. 点击 SQL 标签，复制下面的内容并运行
--
-- 主要功能：
-- - 用户角色系统（买家、卖家、管理员）
-- - 拍卖商品管理
-- - 出价系统
-- - 举报系统
-- - 管理员审核和下架功能
-- ========================================

USE auction_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ==============
-- roles
-- ==============
-- 系统角色：
-- role_id=1: buyer (买家)
-- role_id=2: seller (卖家)
-- role_id=3: admin (管理员)
DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
  role_id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ==============
-- users
-- ==============
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    role_id        INT NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id)
        REFERENCES roles(role_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- categories
-- ==============
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    category_id    INT AUTO_INCREMENT PRIMARY KEY,
    category_name  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ==============
-- items
-- ==============
DROP TABLE IF EXISTS items;
CREATE TABLE items (
    item_id      INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    description  TEXT,
    
    -- 👇 新增的这一行 👇
    image_path   VARCHAR(255) DEFAULT NULL, 
    -- 👆 新增的这一行 👆

    category_id  INT NOT NULL,
    seller_id    INT NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_items_category
        FOREIGN KEY (category_id)
        REFERENCES categories(category_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_items_seller
        FOREIGN KEY (seller_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- auctions
-- ==============
DROP TABLE IF EXISTS auctions;
CREATE TABLE auctions (
    auction_id    INT AUTO_INCREMENT PRIMARY KEY,
    item_id       INT NOT NULL,
    seller_id     INT NOT NULL,
    start_price   DECIMAL(10,2) NOT NULL,
    reserve_price DECIMAL(10,2),
    start_date    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_date      DATETIME NOT NULL,
    winner_id     INT NULL,
    -- Status 说明:
    -- pending: 待开始  active: 进行中  finished: 已结束
    -- cancelled: 已取消  removed: 已下架（管理员操作）
    status        ENUM('pending','active','finished','cancelled','removed')
                  NOT NULL DEFAULT 'pending',

    CONSTRAINT fk_auctions_item
        FOREIGN KEY (item_id)
        REFERENCES items(item_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_auctions_seller
        FOREIGN KEY (seller_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_auctions_winner
        FOREIGN KEY (winner_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- bids
-- ==============
DROP TABLE IF EXISTS bids;
CREATE TABLE bids (
    bid_id      INT AUTO_INCREMENT PRIMARY KEY,
    auction_id  INT NOT NULL,
    buyer_id    INT NOT NULL,
    bid_amount  DECIMAL(10,2) NOT NULL,
    bid_time    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_bids_auction
        FOREIGN KEY (auction_id)
        REFERENCES auctions(auction_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_bids_buyer
        FOREIGN KEY (buyer_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- payments
-- ==============
DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,        -- 付款的买家
    auction_id      INT NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    payment_method  VARCHAR(50) NOT NULL,
    status          VARCHAR(50) NOT NULL,
    paid_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_payments_auction
        FOREIGN KEY (auction_id)
        REFERENCES auctions(auction_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- reports
-- ==============
DROP TABLE IF EXISTS reports;
CREATE TABLE reports (
    report_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,      -- 举报人
    auction_id   INT NULL,
    item_id      INT NULL,
    description  TEXT NOT NULL,
    status       VARCHAR(50) NOT NULL DEFAULT 'open',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reports_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_reports_auction
        FOREIGN KEY (auction_id)
        REFERENCES auctions(auction_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_reports_item
        FOREIGN KEY (item_id)
        REFERENCES items(item_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- recommendations
-- ==============
DROP TABLE IF EXISTS recommendations;
CREATE TABLE recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    item_id           INT NOT NULL,
    reason            VARCHAR(255),
    score             DECIMAL(5,2),

    CONSTRAINT fk_recommendations_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_recommendations_item
        FOREIGN KEY (item_id)
        REFERENCES items(item_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- favourites
-- ==============
DROP TABLE IF EXISTS favourites;
CREATE TABLE favourites (
    user_id  INT NOT NULL,
    item_id  INT NOT NULL,

    PRIMARY KEY (user_id, item_id),

    CONSTRAINT fk_favourites_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_favourites_item
        FOREIGN KEY (item_id)
        REFERENCES items(item_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- watchlist
-- ==============
DROP TABLE IF EXISTS watchlist;
CREATE TABLE watchlist (
    user_id    INT NOT NULL,
    auction_id INT NOT NULL,

    PRIMARY KEY (user_id, auction_id),

    CONSTRAINT fk_watchlist_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_watchlist_auction
        FOREIGN KEY (auction_id)
        REFERENCES auctions(auction_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============
-- autobids
-- ==============
DROP TABLE IF EXISTS autobids;
CREATE TABLE autobids (
    autobid_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    auction_id  INT NOT NULL,
    max_amount  DECIMAL(10,2) NOT NULL,
    step        DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_autobids_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_autobids_auction
        FOREIGN KEY (auction_id)
        REFERENCES auctions(auction_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
-- ===========================
-- Notifications Table
-- ===========================
DROP TABLE IF EXISTS notifications;

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- 数据库结构创建完成！
-- ========================================
--
-- 下一步：
-- 1. 运行 seed.sql 插入测试数据（可选）
-- 2. 访问 create_initial_admin.php 创建管理员账号
--
-- 重要更新：
-- ✓ auctions 表的 status 字段新增 'removed' 状态
--   - 管理员可以将违规拍品标记为 'removed'
--   - 已下架的拍品不会显示在浏览页面
--
-- ✓ roles 表支持三种角色：
--   - buyer (role_id=1): 买家
--   - seller (role_id=2): 卖家
--   - admin (role_id=3): 管理员
--
-- ========================================
