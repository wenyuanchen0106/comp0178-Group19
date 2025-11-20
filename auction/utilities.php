<?php

// ----------------------------------------------------
// 1. 会话 & 数据库基础配置（在老师模板前面“加一段”）
// ----------------------------------------------------

// 启动 Session（如果别的文件已经启动也不会报错）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== 数据库连接配置 =====
// XAMPP 默认：host=localhost, user=root, 密码为空
// 数据库名改成你在 phpMyAdmin 里建的名字（例如 auction_db）
define('DB_HOST', 'localhost');
define('DB_NAME', 'auction_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * 获取全局唯一的 mysqli 连接
 * 以后所有 PHP 文件都通过 get_db() 来拿连接，不要自己 new mysqli
 */
function get_db() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            // 课程项目为了调试方便，可以直接 die 出错误信息
            die('Database connection failed: ' . $conn->connect_error);
        }

        $conn->set_charset(DB_CHARSET);
    }

    return $conn;
}

/**
 * 执行带预处理的 SELECT 查询
 *  - $sql   : 含 ? 占位符的 SQL
 *  - $types : 例如 "si"（string, int）
 *  - $params: 参数数组
 * 返回 mysqli_result 或 false
 */
function db_query($sql, $types = '', $params = []) {
    $conn = get_db();

    if ($types === '' || empty($params)) {
        // 无参数，直接普通 query
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
 * 执行 INSERT / UPDATE / DELETE
 * 返回受影响行数
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
 * 当前是否已登录
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * 当前用户 ID（未登录返回 null）
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * 当前用户角色（在登录时把 role_name 放进 session）
 */
function current_user_role() {
    return $_SESSION['role_name'] ?? null;
}

/**
 * 简单重定向工具
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// 方便把查询结果一次性取成数组：[['col'=>...], ...]
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

// 自动把已经到结束时间的拍卖结算掉：改成 finished，写 winner_id
function close_expired_auctions() {
    $now = date('Y-m-d H:i:s');

    // 找所有已经过期但还没 finished/cancelled 的拍卖
    $sql = "
        SELECT auction_id
        FROM auctions
        WHERE end_date <= ?
          AND status IN ('pending', 'active')
    ";
    $expired = db_fetch_all($sql, 's', [$now]);

    foreach ($expired as $row) {
        $auction_id = (int)$row['auction_id'];

        // 找当前最高出价
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

        // 根据有没有 winner 分成两种 UPDATE，避免 NULL 绑定成 0 的问题
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


//原有功能和模版

// display_time_remaining:
// Helper function to help figure out what time to display
function display_time_remaining($interval) {

    if ($interval->days == 0 && $interval->h == 0) {
      // Less than one hour remaining: print mins + seconds:
      $time_remaining = $interval->format('%im %Ss');
    }
    else if ($interval->days == 0) {
      // Less than one day remaining: print hrs + mins:
      $time_remaining = $interval->format('%hh %im');
    }
    else {
      // At least one day remaining: print days + hrs:
      $time_remaining = $interval->format('%ad %hh');
    }

  return $time_remaining;

}

// print_listing_li:
// This function prints an HTML <li> element containing an auction listing
function print_listing_li($item_id, $title, $desc, $price, $num_bids, $end_time)
{
  // Truncate long descriptions
  if (strlen($desc) > 250) {
    $desc_shortened = substr($desc, 0, 250) . '...';
  }
  else {
    $desc_shortened = $desc;
  }
  
  // Fix language of bid vs. bids
  if ($num_bids == 1) {
    $bid = ' bid';
  }
  else {
    $bid = ' bids';
  }
  
  // Calculate time to auction end
  $now = new DateTime();
  if ($now > $end_time) {
    $time_remaining = 'This auction has ended';
  }
  else {
    // Get interval:
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = display_time_remaining($time_to_end) . ' remaining';
  }
  
  // Print HTML
  echo('
    <li class="list-group-item d-flex justify-content-between">
    <div class="p-2 mr-5"><h5><a href="listing.php?item_id=' . $item_id . '">' . $title . '</a></h5>' . $desc_shortened . '</div>
    <div class="text-center text-nowrap"><span style="font-size: 1.5em">£' . number_format($price, 2) . '</span><br/>' . $num_bids . $bid . '<br/>' . $time_remaining . '</div>
  </li>'
  );
}

?>