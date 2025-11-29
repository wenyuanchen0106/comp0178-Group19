<?php
require_once("utilities.php");
include_once("header.php");

// 1. 检查登录
if (!is_logged_in()) {
    echo '<div class="container mt-4"><div class="alert alert-danger text-center">Please log in to check recommendations.</div></div>';
    include_once("footer.php");
    exit;
}

$user_id = $_SESSION['user_id'];
?>

<div class="container mt-4">
    <h2 class="my-3 text-uppercase" style="font-family: 'Oswald', sans-serif; letter-spacing: 1px;">Recommendations for You</h2>

    <?php
    // ======================================================
    // 2. 逻辑优化：一步查出用户最近参与竞拍的商品的 category_id
    // ======================================================
    // 我们不需要手动 new mysqli，直接用 utilities.php 里的 db_query
    
    $sql_recent = "
        SELECT i.category_id, i.item_id
        FROM bids b
        JOIN auctions a ON b.auction_id = a.auction_id
        JOIN items i ON a.item_id = i.item_id
        WHERE b.buyer_id = ?
        ORDER BY b.bid_time DESC
        LIMIT 1
    ";

    $result_recent = db_query($sql_recent, 'i', [$user_id]);

    if (!$result_recent || $result_recent->num_rows == 0) {
        // 没有出过价
        echo '<div class="text-center py-5">';
        echo '<p class="text-muted mb-4">No past bids found. Browse items to get recommendations.</p>';
        echo '<a href="browse.php" class="btn btn-primary btn-lg">Browse Auctions</a>';
        echo '</div>';
    } else {
        // 找到了最近的出价
        $last_row = $result_recent->fetch_assoc();
        $target_cat_id = $last_row['category_id'];
        $exclude_item_id = $last_row['item_id']; // 排除掉自己刚投过的那个

        // ======================================================
        // 3. 查找同类推荐商品 (核心：加上 image_path)
        // ======================================================
        $rec_sql = "
            SELECT item_id, title, description, image_path
            FROM items
            WHERE category_id = ?
              AND item_id != ?
            LIMIT 4
        ";
        
        // 这里的 'ii' 代表两个整数参数
        $rec_result = db_query($rec_sql, 'ii', [$target_cat_id, $exclude_item_id]);

        if ($rec_result->num_rows == 0) {
            echo "<p class='text-muted'>No other recommended items in this category right now.</p>";
        } else {
            echo "<p class='text-light mb-4' style='border-left: 3px solid var(--color-primary); padding-left: 10px;'>Based on your recent bid, you may like:</p>";
            
            echo '<div class="row">';
            while ($row = $rec_result->fetch_assoc()) {
                $img_path = $row['image_path'] ?? null;
                $title = $row['title'];
                // 描述太长就截断
                $desc = (strlen($row['description']) > 80) ? substr($row['description'], 0, 80) . '...' : $row['description'];
                $r_item_id = $row['item_id'];
    ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 shadow-sm" style="background-color: var(--color-bg-secondary); border: 1px solid #444; transition: transform 0.2s;">
                        
                        <?php if (!empty($img_path) && file_exists("images/" . $img_path)): ?>
                            <div style="height: 180px; overflow: hidden; border-bottom: 1px solid var(--color-primary);">
                                <img src="images/<?php echo $img_path; ?>" class="card-img-top" alt="Item" style="height: 100%; width: 100%; object-fit: cover;">
                            </div>
                        <?php else: ?>
                            <div class="img-placeholder" style="width: 100%; height: 180px; margin: 0; border: none; border-bottom: 1px solid var(--color-primary); border-radius: 4px 4px 0 0;"></div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title" style="font-size: 1.1rem; min-height: 3rem;">
                                <a href="listing.php?item_id=<?php echo $r_item_id; ?>" class="text-light" style="font-family: 'Oswald', sans-serif;">
                                    <?php echo htmlspecialchars($title); ?>
                                </a>
                            </h5>
                            <p class="card-text small text-muted flex-grow-1">
                                <?php echo htmlspecialchars($desc); ?>
                            </p>
                            
                            <a href="listing.php?item_id=<?php echo $r_item_id; ?>" class="btn btn-block btn-outline-danger btn-sm mt-3">View Item</a>
                        </div>
                    </div>
                </div>
    <?php
            } // end while
            echo '</div>'; // end row
        }
    }
    ?>

</div>

<?php include_once("footer.php"); ?>