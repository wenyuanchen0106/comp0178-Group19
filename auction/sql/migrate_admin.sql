-- ========================================
-- Database migration script: add admin features
-- ========================================
-- This script adds admin functionality on top of an existing database.
-- If you have already run schema.sql and seed.sql, run this script
-- to enable admin features.
--
-- How to use:
-- 1. Log in to phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Select the auction_db database
-- 3. Click the SQL tab
-- 4. Paste this script and execute it
-- ========================================

USE auction_db;

-- ========================================
-- 1. Add admin role (if it does not exist)
-- ========================================
INSERT IGNORE INTO roles (role_id, role_name) VALUES (3, 'admin');

-- ========================================
-- 2. Update auctions.status ENUM to include 'removed'
-- ========================================
-- If your current ENUM does not contain 'removed', this will extend it
ALTER TABLE auctions
MODIFY COLUMN status ENUM('pending','active','finished','cancelled','removed')
NOT NULL DEFAULT 'pending';

-- ========================================
-- Database structure update completed!
-- ========================================
--
-- Next step: create the initial admin account
-- ========================================
-- Because the password must be hashed using PHP's password_hash(),
-- we do not create the admin user directly in SQL here.
--
-- Option 1 (recommended): use create_initial_admin.php
-- ----------------------------------------------------
-- 1. In your browser, visit:
--    http://localhost/auction/create_initial_admin.php
--
-- 2. That page will automatically create an admin account:
--    Email: admin@auction.com
--    Password: password123
--
-- 3. After successful creation, immediately delete create_initial_admin.php
--
--
-- Option 2: use the admin management page
-- ----------------------------------------------------
-- 1. First register a normal account via the site
-- 2. In phpMyAdmin, manually change that user’s role_id to 3
-- 3. Log in with this account and open manage_admins.php
--    to create additional admins
--
--
-- Admin capabilities (after this migration):
-- ----------------------------------------------------
-- 1. View all reports: open admin_reports.php
-- 2. Remove auctions: click "Remove Auction" on the report page
-- 3. Create new admins: open manage_admins.php
-- 4. The admin menu will automatically appear in the navigation bar
-- ========================================

