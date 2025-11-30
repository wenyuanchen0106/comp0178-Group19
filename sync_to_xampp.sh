#!/bin/bash
# 同步修改的文件到XAMPP目录

SOURCE_DIR="/Users/lilangran/Desktop/DB_Group/comp0178-Group19/auction"
XAMPP_DIR="/Applications/XAMPP/xamppfiles/htdocs/auction"

echo "开始同步文件到XAMPP..."
echo ""

# SQL文件
echo "1. 同步SQL文件..."
sudo cp "$SOURCE_DIR/sql/schema.sql" "$XAMPP_DIR/sql/schema.sql"
sudo cp "$SOURCE_DIR/sql/seed.sql" "$XAMPP_DIR/sql/seed.sql"
sudo cp "$SOURCE_DIR/sql/migrate_admin.sql" "$XAMPP_DIR/sql/migrate_admin.sql"
echo "   ✓ SQL文件同步完成"

# PHP文件 - 新建的
echo "2. 复制新建的PHP文件..."
sudo cp "$SOURCE_DIR/manage_admins.php" "$XAMPP_DIR/manage_admins.php"
sudo cp "$SOURCE_DIR/remove_auction.php" "$XAMPP_DIR/remove_auction.php"
echo "   ✓ 新建文件复制完成"

# PHP文件 - 修改的
echo "3. 同步修改的PHP文件..."
sudo cp "$SOURCE_DIR/admin_reports.php" "$XAMPP_DIR/admin_reports.php"
sudo cp "$SOURCE_DIR/header.php" "$XAMPP_DIR/header.php"
sudo cp "$SOURCE_DIR/listing.php" "$XAMPP_DIR/listing.php"
sudo cp "$SOURCE_DIR/login_result.php" "$XAMPP_DIR/login_result.php"
echo "   ✓ 修改文件同步完成"

# 创建管理员脚本
echo "4. 复制创建管理员脚本..."
sudo cp "$SOURCE_DIR/create_initial_admin.php" "$XAMPP_DIR/create_initial_admin.php"
echo "   ✓ 创建管理员脚本复制完成"

# 设置正确的权限
echo "5. 设置文件权限..."
sudo chown -R daemon:daemon "$XAMPP_DIR"/*.php
sudo chown -R daemon:daemon "$XAMPP_DIR/sql"/*.sql
echo "   ✓ 权限设置完成"

echo ""
echo "=========================================="
echo "同步完成！"
echo "=========================================="
echo ""
echo "已同步的文件："
echo "  SQL:"
echo "    - sql/schema.sql (修改)"
echo "    - sql/seed.sql (修改)"
echo "    - sql/migrate_admin.sql (新建)"
echo ""
echo "  PHP:"
echo "    - manage_admins.php (新建)"
echo "    - remove_auction.php (新建)"
echo "    - create_initial_admin.php (新建)"
echo "    - admin_reports.php (修改)"
echo "    - header.php (修改)"
echo "    - listing.php (修改)"
echo "    - login_result.php (修改)"
echo ""
echo "下一步："
echo "1. 在phpMyAdmin中运行 sql/migrate_admin.sql 更新数据库结构"
echo "2. 访问 http://localhost/auction/create_initial_admin.php 创建管理员"
echo "3. 使用 admin@auction.com / password123 登录测试"
echo "4. 登录成功后立即删除 create_initial_admin.php 文件"
echo ""
