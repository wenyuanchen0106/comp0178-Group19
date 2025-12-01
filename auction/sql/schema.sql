-- ========================================
-- Auction system database schema
-- ========================================
-- Main features:
-- - User role system (buyer, seller, admin)
-- - Auction item management
-- - Bidding system
-- - Reporting system
-- - Admin review and removal functionality
-- ========================================

USE auction_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ==============
-- roles
-- ==============
-- System roles:
-- role_id=1: buyer
-- role_id=2: seller
-- role_id=3: admin
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
    image_path   VARCHAR(255) DEFAULT NULL,
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
    -- Status values:
    -- pending: not started yet
    -- active: running
    -- finished: ended
    -- cancelled: cancelled by seller
    -- removed: removed by admin
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
    user_id         INT NOT NULL,        -- paying buyer
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
    user_id      INT NOT NULL,      -- reporting user
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
-- notifications
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
-- Database schema created
-- ========================================


