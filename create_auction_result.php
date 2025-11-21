<?php require_once 'utilities.php';
include_once("header.php");
?>

<div class="container my-5">

<?php

// This function takes the form data and adds the new auction to the database.

/* TODO #1: Connect to MySQL database (perhaps by requiring a file that
            already does this). */


// ------------ 1. 权限检查：必须是已登录的 seller -------------
if (!is_logged_in() || current_user_role() !== 'seller') {
    echo '<div class="alert alert-danger text-center">
            You must be logged in as a seller to create an auction.
          </div>';
} else {

    // ------------ 2. 读取并清洗表单数据 -----------------
    $title         = trim($_POST['title']         ?? '');
    $details       = trim($_POST['details']       ?? '');
    $category_raw  = $_POST['category']           ?? '';
    $start_price   = $_POST['start_price']        ?? '';
    $reserve_price = $_POST['reserve_price']      ?? '';
    $end_date_raw  = trim($_POST['end_date']      ?? '');

    $errors = [];

    // 基本非空检查
    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if ($details === '') {
        $errors[] = 'Details are required.';
    }
    if ($category_raw === '') {
        $errors[] = 'Category is required.';
    }

    // 价格检查
    if ($start_price === '' || !is_numeric($start_price) || $start_price < 0) {
        $errors[] = 'Starting price must be a non-negative number.';
    }

    if ($reserve_price !== '' && (!is_numeric($reserve_price) || $reserve_price < 0)) {
        $errors[] = 'Reserve price must be a non-negative number (or left empty).';
    }

    // 结束时间检查：datetime-local 传过来类似 2025-11-20T23:59
    if ($end_date_raw === '') {
        $errors[] = 'End date/time is required.';
    } else {
        $end_date = str_replace('T', ' ', $end_date_raw) . ':00';   // 转成 MySQL DATETIME
        $now      = date('Y-m-d H:i:s');
        if ($end_date <= $now) {
            $errors[] = 'End time must be in the future.';
        }
    }

    // category_id 转成整数（下拉框后面让你们把 value 改成实际 category_id）
    $category_id = (int)$category_raw;
    $seller_id   = current_user_id();

    
/* TODO #2: Extract form data into variables. Because the form was a 'post'
            form, its data can be accessed via $POST['auctionTitle'], 
            $POST['auctionDetails'], etc. Perform checking on the data to
            make sure it can be inserted into the database. If there is an
            issue, give some semi-helpful feedback to user. */



    // ------------ 3. 如果有错误，显示错误并不给插入 ----------
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($errors as $e) {
            echo '<li>' . htmlspecialchars($e) . '</li>';
        }
        echo '</ul>
              <div class="text-center mt-3">
                <a href="create_auction.php" class="btn btn-secondary">Back to form</a>
              </div>
            </div>';
    } else {

        
/* TODO #3: If everything looks good, make the appropriate call to insert
            data into the database. */
            

        // ------------ 4. 写入数据库：items + auctions -----------

        // （1）插入 items
        db_execute(
            "INSERT INTO items (title, description, category_id, seller_id)
             VALUES (?, ?, ?, ?)",
            "ssii",
            [$title, $details, $category_id, $seller_id]
        );

        $conn    = get_db();
        $item_id = $conn->insert_id;   // 新插入的 item_id

        // （2）插入 auctions
        $start_price_f   = (float)$start_price;
        $reserve_price_f = ($reserve_price === '' ? null : (float)$reserve_price);
        $start_date      = date('Y-m-d H:i:s');
        $status          = 'open';

        // 注意 reserve_price 可能为 NULL，所以这里分情况
        if ($reserve_price_f === null) {
            db_execute(
                "INSERT INTO auctions
                 (item_id, seller_id, start_price, reserve_price,
                  start_date, end_date, winner_id, status)
                 VALUES (?, ?, ?, NULL, ?, ?, NULL, ?)",
                "iddsss",
                [$item_id, $seller_id, $start_price_f, $start_date, $end_date, $status]
            );
        } else {
            db_execute(
                "INSERT INTO auctions
                 (item_id, seller_id, start_price, reserve_price,
                  start_date, end_date, winner_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, NULL, ?)",
                "idddsss",
                [$item_id, $seller_id, $start_price_f, $reserve_price_f,
                 $start_date, $end_date, $status]
            );
        }

// If all is successful, let user know.
        // 成功信息 + 跳转链接（Starter 的 listing.php 用的是 item_id 参数）
        $link = 'listing.php?item_id=' . urlencode($item_id);



echo('<div class="text-center">Auction successfully created! <a href="' . $link . '">View your new listing.</a></div>');
    }
}    

?>

</div>


<?php include_once("footer.php")?>