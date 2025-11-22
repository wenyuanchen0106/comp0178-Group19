<?php
session_start();
require_once("utilities.php");
include_once("header.php");
?>

<div class="container">

<h2 class="my-3">Recommendations for you</h2>

<?php

// 1. 未登录 → 提示
if (!isset($_SESSION['user_id'])) {
    echo "<p>Please log in to see recommendations.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. 连接数据库
$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "auction_db";

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("connection failed: " . $conn->connect_error);
}

// 3. 找出用户最近一次出价对应的 item_id（正确写法：bids → auctions → items）
$sql = "
SELECT a.item_id
FROM bids b
JOIN auctions a ON b.auction_id = a.auction_id
WHERE b.buyer_id = $user_id
ORDER BY b.bid_time DESC
LIMIT 1;
";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<p>No past bids found. Browse items to get recommendations.</p>";
    exit;
}

$last = $result->fetch_assoc();
$item_id = $last['item_id'];

// 4. 查该 item 的 category
$cat_sql = "SELECT category_id FROM items WHERE item_id = $item_id";
$cat_result = $conn->query($cat_sql);

if ($cat_result->num_rows == 0) {
    echo "<p>Item category not found.</p>";
    exit;
}

$category_id = $cat_result->fetch_assoc()['category_id'];

// 5. 查找该分类下除了该 item 的其它商品（推荐）
$rec_sql = "
SELECT item_id, title, description
FROM items
WHERE category_id = $category_id
  AND item_id != $item_id
LIMIT 5;
";

$rec_result = $conn->query($rec_sql);

if ($rec_result->num_rows == 0) {
    echo "<p>No other recommended items in this category.</p>";
} else {
    echo "<p>Based on your recent bid, you may like:</p>";

    while ($row = $rec_result->fetch_assoc()) {
        echo '<div class="card p-3 my-2">';
        echo '<h4>' . htmlspecialchars($row['title']) . '</h4>';
        echo '<p>' . htmlspecialchars($row['description']) . '</p>';
        echo '<a href="listing.php?item_id=' . $row['item_id'] . '">View item</a>';
        echo '</div>';
    }
}

?>

</div>

<?php include_once("footer.php"); ?>
