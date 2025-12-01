<?php
// Process auction creation form and insert a new auction into the system

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utilities.php';
include_once("header.php");
?>

<div class="container my-5">

<?php

// This function takes the form data and adds the new auction to the database.

/* TODO #1: Connect to MySQL database (perhaps by requiring a file that
            already does this). */

// ------------ 1. Permission check: must be logged in as seller -------------
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] != 'seller') {
    echo '<div class="alert alert-danger text-center">
            You must be logged in as a seller to create an auction.
          </div>';
} else {

    // ------------ 2. Read and sanitise form data -----------------
    $title          = trim($_POST['title']         ?? '');
    $details        = trim($_POST['details']       ?? '');
    $category_raw   = $_POST['category']           ?? '';
    $start_price    = $_POST['start_price']        ?? '';
    $reserve_price  = $_POST['reserve_price']      ?? '';
    $start_date_raw = trim($_POST['start_date']    ?? '');
    $end_date_raw   = trim($_POST['end_date']      ?? '');

    $errors = [];

    // Basic required field checks
    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if ($details === '') {
        $errors[] = 'Details are required.';
    }
    if ($category_raw === '') {
        $errors[] = 'Category is required.';
    }

    // Price validation
    if ($start_price === '' || !is_numeric($start_price) || $start_price < 0) {
        $errors[] = 'Starting price must be a non-negative number.';
    }

    if ($reserve_price !== '' && (!is_numeric($reserve_price) || $reserve_price < 0)) {
        $errors[] = 'Reserve price must be a non-negative number (or left empty).';
    }

    // Ensure reserve price is greater than or equal to starting price
    if ($reserve_price !== '' && is_numeric($reserve_price) && is_numeric($start_price)) {
        if ((float)$reserve_price < (float)$start_price) {
            $errors[] = 'Reserve price must be greater than or equal to starting price.';
        }
    }

    // Start time check: datetime-local format like 2025-11-20T23:59
    $now = date('Y-m-d H:i:s');
    if ($start_date_raw === '') {
        // If no start time is provided, default to current time
        $start_date = $now;
    } else {
        $start_date = str_replace('T', ' ', $start_date_raw) . ':00';   // Convert to MySQL DATETIME
    }

    // End time check: datetime-local format like 2025-11-20T23:59
    if ($end_date_raw === '') {
        $errors[] = 'End date/time is required.';
    } else {
        $end_date = str_replace('T', ' ', $end_date_raw) . ':00';   // Convert to MySQL DATETIME
        if ($end_date <= $now) {
            $errors[] = 'End time must be in the future.';
        }
        // Ensure start time is before end time
        if ($start_date >= $end_date) {
            $errors[] = 'Start time must be before end time.';
        }
    }

    // Cast category_id to integer (dropdown value should be actual category_id)
    $category_id = (int)$category_raw;
    $seller_id   = current_user_id();

/* TODO #2: Extract form data into variables. Because the form was a 'post'
            form, its data can be accessed via $POST['auctionTitle'], 
            $POST['auctionDetails'], etc. Perform checking on the data to
            make sure it can be inserted into the database. If there is an
            issue, give some semi-helpful feedback to user. */

    // ------------ 3. If there are validation errors, show them and do not insert ----------
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

        $image_path = null;

        if (isset($_FILES['auction_image']) && $_FILES['auction_image']['error'] == UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['auction_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . '/images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $new_filename = 'item_' . uniqid() . '.' . $ext;
                $destination = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['auction_image']['tmp_name'], $destination)) {
                    $image_path = 'images/' . $new_filename;
                }
            }
        }

        // ------------ 4. Insert into database: items + auctions -----------
        db_execute(
            "INSERT INTO items (title, description, image_path, category_id, seller_id)
             VALUES (?, ?, ?, ?, ?)",
            "sssii",
            [$title, $details, $image_path, $category_id, $seller_id]
        );

        $conn = get_db();
        $item_id = $conn->insert_id;

        $start_price_f   = (float)$start_price;
        $reserve_price_f = ($reserve_price === '' ? null : (float)$reserve_price);

        $status = ($start_date > $now) ? 'pending' : 'active';

        if ($reserve_price_f === null) {
            db_execute(
                "INSERT INTO auctions
                 (item_id, seller_id, start_price, reserve_price,
                  start_date, end_date, winner_id, status)
                 VALUES (?, ?, ?, NULL, ?, ?, NULL, ?)",
                "iidsss",
                [$item_id, $seller_id, $start_price_f, $start_date, $end_date, $status]
            );
        } else {
            db_execute(
                "INSERT INTO auctions
                 (item_id, seller_id, start_price, reserve_price,
                  start_date, end_date, winner_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, NULL, ?)",
                "iiddsss",
                [$item_id, $seller_id, $start_price_f, $reserve_price_f,
                 $start_date, $end_date, $status]
            );
        }

        // If all is successful, let user know.
        $link = 'listing.php?item_id=' . urlencode($item_id);
        echo '<div class="text-center">Auction successfully created! <a href="' . $link . '">View your new listing.</a></div>';
    }
}

?>

</div>

<?php include_once("footer.php")?>

