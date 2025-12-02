<?php

// ----------------------------------------------------
// 1. Session & database bootstrap (placed before original template code)
// ----------------------------------------------------

// Start session (safe even if another file already called session_start())
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== Database connection config =====
// Default XAMPP: host=localhost, user=root, empty password
// Change DB_NAME to the schema name you created in phpMyAdmin (e.g. auction_db)
define('DB_HOST', 'localhost');
define('DB_NAME', 'auction_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get a single shared mysqli connection.
 * All PHP files should use get_db() instead of creating new mysqli instances.
 */
function get_db() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            // For coursework it is acceptable to die with an error message
            die('Database connection failed: ' . $conn->connect_error);
        }

        $conn->set_charset(DB_CHARSET);
    }

    return $conn;
}

/**
 * Run a SELECT query using a prepared statement.
 *  - $sql   : SQL with ? placeholders
 *  - $types : e.g. "si" (string, int)
 *  - $params: parameter array
 * Returns mysqli_result or false.
 */
function db_query($sql, $types = '', $params = []) {
    $conn = get_db();

    if ($types === '' || empty($params)) {
        // No parameters → simple query
        return $conn->query($sql);
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        die('Execute failed: ' . $stmt->error);
    }

    return $stmt->get_result();
}

/**
 * Run INSERT / UPDATE / DELETE using a prepared statement.
 * Returns the number of affected rows.
 */
function db_execute($sql, $types = '', $params = []) {
    $conn = get_db();

    if ($types === '' || empty($params)) {
        $ok = $conn->query($sql);
        if ($ok === false) {
            die('Query failed: ' . $conn->error);
        }
        return $conn->affected_rows;
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        die('Execute failed: ' . $stmt->error);
    }

    return $stmt->affected_rows;
}

/**
 * Check whether a user is logged in.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Force login for a page: redirect to login.php if not logged in.
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Get current user id from session (or null if not logged in).
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role name from session (buyer / seller / admin).
 */
function current_user_role() {
    return $_SESSION['role_name'] ?? null;
}

/**
 * Simple redirect helper.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Helper: run a query and return all rows as an array of associative arrays.
 */
function db_fetch_all($sql, $types = '', $params = []) {
    $rows = [];

    $result = db_query($sql, $types, $params);
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }

    return $rows;
}

/**
 * Automatically close expired auctions:
 * - For auctions whose end_date has passed and status is pending/active,
 *   find the highest bid and set status='finished' and winner_id accordingly.
 */
function close_expired_auctions() {
    $now = date('Y-m-d H:i:s');

    $sql = "
        SELECT auction_id
        FROM auctions
        WHERE end_date <= ?
          AND status IN ('pending', 'active')
    ";
    $expired = db_fetch_all($sql, 's', [$now]);

    foreach ($expired as $row) {
        $auction_id = (int)$row['auction_id'];

        $sqlMax = "
            SELECT buyer_id, bid_amount
            FROM bids
            WHERE auction_id = ?
            ORDER BY bid_amount DESC, bid_time ASC
            LIMIT 1
        ";
        $resultMax = db_query($sqlMax, 'i', [$auction_id]);

        $winner_id = null;

        if ($resultMax instanceof mysqli_result && $resultMax->num_rows > 0) {
            $maxBid = $resultMax->fetch_assoc();
            $winner_id = (int)$maxBid['buyer_id'];
            $resultMax->free();
        }

        // Use two separate UPDATE statements to avoid binding NULL as 0
        if ($winner_id === null) {
            $sqlUpdate = "
                UPDATE auctions
                SET status = 'finished',
                    winner_id = NULL
                WHERE auction_id = ?
            ";
            db_execute($sqlUpdate, 'i', [$auction_id]);
        } else {
            $sqlUpdate = "
                UPDATE auctions
                SET status = 'finished',
                    winner_id = ?
                WHERE auction_id = ?
            ";
            db_execute($sqlUpdate, 'ii', [$winner_id, $auction_id]);
        }
    }
}

/**
 * Activate pending auctions whose start_date has been reached.
 */
function activate_pending_auctions() {
    $now = date('Y-m-d H:i:s');

    $sql = "
        UPDATE auctions
        SET status = 'active'
        WHERE status = 'pending'
          AND start_date <= ?
    ";
    db_execute($sql, 's', [$now]);
}

// display_time_remaining:
// Helper function to format a DateInterval into a compact remaining time string.
function display_time_remaining($interval) {

    if ($interval->days == 0 && $interval->h == 0) {
      // Less than one hour remaining: mins + seconds
      $time_remaining = $interval->format('%im %Ss');
    }
    else if ($interval->days == 0) {
      // Less than one day remaining: hours + mins
      $time_remaining = $interval->format('%hh %im');
    }
    else {
      // At least one day remaining: days + hours
      $time_remaining = $interval->format('%ad %hh');
    }

  return $time_remaining;

}

/**
 * Fallback notification helper (in case another send_notification is not defined).
 * Inserts a row into notifications table with is_read=0 and current timestamp.
 */
if (!function_exists('send_notification')) {
    function send_notification($user_id, $title, $message, $link) {
        db_execute(
            "INSERT INTO notifications (user_id, title, message, link, is_read, created_at)
             VALUES (?, ?, ?, ?, 0, NOW())",
            "isss",
            [$user_id, $title, $message, $link]
        );
    }
}

// print_listing_li:
// Print an HTML <li> element for a single auction listing.
function print_listing_li($item_id, $title, $desc, $price, $num_bids, $end_time, $image_path = null, $current_winner = null)
{
  // Shorten long descriptions for list view
  if (strlen($desc) > 250) {
    $desc_shortened = substr($desc, 0, 250) . '...';
  }
  else {
    $desc_shortened = $desc;
  }
  
  // Singular vs plural bid/bids
  if ($num_bids == 1) {
    $bid = ' bid';
  }
  else {
    $bid = ' bids';
  }
  
  // Compute remaining time or show that auction ended
  $now = new DateTime();
  if ($now > $end_time) {
    $time_remaining = 'This auction has ended';
  }
  else {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = display_time_remaining($time_to_end) . ' remaining';
  }

  // Image handling: real image if file exists, otherwise placeholder block
  $img_html = '';
 // --- Image handling ---
 // Extract filename only
 $filename = basename($image_path ?? '');

 $real_path = __DIR__ . "/images/" . $filename;   // absolute path for file_exists
 $web_path  = "images/" . $filename;              // relative path for <img>

 if (!empty($filename) && file_exists($real_path)) {

    // Real image
    $img_html = '<img src="' . $web_path . '" alt="' . htmlspecialchars($title) . '"
                 style="width:150px;height:150px;object-fit:cover;border-radius:4px;
                 margin-right:20px;border:1px solid #333;">';

 } else {

    // Placeholder
    $img_html = '<div class="img-placeholder"></div>';

}

  
  echo('
    <li class="list-group-item d-flex justify-content-between align-items-center">
    
    ' . $img_html . '

    <div class="p-2 mr-5 flex-grow-1">
        <h5><a href="listing.php?item_id=' . $item_id . '">' . $title . '</a></h5>
        ' . $desc_shortened . '
    </div>
    
    <div class="text-center text-nowrap">
        <span style="font-size: 1.5em; color: var(--color-accent); font-weight:bold;">£' . number_format($price, 2) . '</span><br/>
        ' . $num_bids . $bid . '<br/>'
        . ($current_winner ? '<span class="text-info small">Leading: ' . htmlspecialchars($current_winner) . '</span><br/>' : '') .
        $time_remaining . '
    </div>
  </li>'
  );
}

?>
