<?php 
require_once 'utilities.php'; 
// close_expired_auctions(); // 如果你的 utilities 里没有自动调用这个，可以在这里取消注释
include_once 'header.php'; 
?>

<div class="container">

    <h2 class="my-3 text-uppercase" style="font-family: 'Oswald', sans-serif; letter-spacing: 1px;">My bids</h2>

    <?php
    // 1. 检查用户登录
    if (!is_logged_in()) {
        echo '<div class="alert alert-danger text-center my-4">You must be logged in to view your bids.</div>';
        include_once 'footer.php';
        exit();
    }

    $user_id = current_user_id();

    // 2. 查询逻辑 (保持你原本的复杂逻辑，确保 image_path 存在)
    $sql = "
        SELECT 
            a.auction_id, a.item_id, a.end_date, a.status, a.winner_id, 
            i.title, i.description, i.image_path, 
            COALESCE(MAX(b.bid_amount), a.start_price) AS current_price, 
            COUNT(b.bid_id) AS num_bids, 
            MAX(CASE WHEN b.buyer_id = ? THEN b.bid_amount ELSE NULL END) AS my_max_bid,
            MAX(CASE WHEN p.status = 'completed' AND p.user_id = ? THEN 1 ELSE 0 END) AS is_paid 
        FROM 
            auctions a 
        JOIN 
            items i ON a.item_id = i.item_id 
        JOIN 
            bids b ON a.auction_id = b.auction_id
        LEFT JOIN 
            payments p ON a.auction_id = p.auction_id 
        WHERE 
            b.buyer_id = ? 
        GROUP BY 
            a.auction_id
        ORDER BY 
            a.end_date DESC
    ";
    
    $result = db_query($sql, 'iii', [$user_id, $user_id, $user_id]);

    if (!$result || $result->num_rows === 0):
    ?>
        <div class="text-center py-5">
            <p class="text-muted mb-4">You have not placed any bids yet.</p>
            <a href="browse.php" class="btn btn-primary btn-lg">Start Browsing</a>
        </div>
    <?php else: ?>

    <ul class="list-group mb-5" style="border: none;">
        <?php while ($row = $result->fetch_assoc()):
            $item_id = (int)$row['item_id'];
            $title = $row['title'];
            $description = $row['description'];
            $current_price = (float)$row['current_price'];
            $num_bids = (int)$row['num_bids'];
            $end_time = new DateTime($row['end_date']);
            $status = $row['status'];
            $winner_id = $row['winner_id'] !== null ? (int)$row['winner_id'] : null;
            $my_max_bid = $row['my_max_bid'] !== null ? (float)$row['my_max_bid'] : null;
            $paid = (int)$row['is_paid'] === 1;

            // --- 图片逻辑 (新增) ---
            $img_path = $row['image_path'] ?? null;
            $img_html = '';
            if (!empty($img_path) && file_exists("images/" . $img_path)) {
                // 有图
                $img_html = '<img src="images/' . $img_path . '" alt="Item" style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #333;">';
            } else {
                // 无图：显示占位符
                $img_html = '<div class="img-placeholder" style="width: 120px; height: 120px; margin: 0;"></div>';
            }

            // --- 状态逻辑 ---
            $now = new DateTime();
            $ended = ($now >= $end_time || $status === 'finished' || $status === 'cancelled');

            $result_text = '';
            $status_color = ''; // 用于控制左侧边框颜色
            $text_class = '';

            if (!$ended) {
                // 进行中
                if ($my_max_bid !== null && $my_max_bid >= $current_price) {
                    $result_text = 'Currently Winning';
                    $status_color = '#28a745'; // 绿色
                    $text_class = 'text-success';
                } else {
                    $result_text = 'Outbid';
                    $status_color = '#dc3545'; // 红色
                    $text_class = 'text-danger';
                }
            } else {
                // 已结束
                if ($winner_id !== null && $winner_id === $user_id) {
                    $result_text = 'You Won';
                    $status_color = '#28a745'; // 绿色 (也是金色 var(--color-accent) 的好地方，这里用绿表示成功)
                    $text_class = 'text-success';
                } else {
                    $result_text = 'You Lost';
                    $status_color = '#6c757d'; // 灰色
                    $text_class = 'text-muted';
                }
            }
        ?>
        
        <li class="list-group-item d-flex align-items-center" 
            style="background-color: rgba(28, 28, 30, 0.9); border: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px; border-radius: 4px; border-left: 6px solid <?php echo $status_color; ?>;">
            
            <div class="mr-3">
                <?php echo $img_html; ?>
            </div>

            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <h5 class="mb-1">
                        <a href="listing.php?item_id=<?php echo $item_id; ?>" class="text-light" style="font-family: 'Oswald', sans-serif; letter-spacing: 0.5px;">
                            <?php echo htmlspecialchars($title); ?>
                        </a>
                    </h5>
                    <span class="<?php echo $text_class; ?> font-weight-bold text-uppercase" style="font-family: 'Oswald', sans-serif;">
                        <?php echo $result_text; ?>
                    </span>
                </div>

                <p class="mb-1 text-muted small" style="line-height: 1.4;">
                    <?php
                    $desc_short = (strlen($description) > 100) ? substr($description, 0, 100) . '...' : $description;
                    echo htmlspecialchars($desc_short);
                    ?>
                </p>
                
                <small class="text-light">
                    My Bid: <span style="color: var(--color-accent);">£<?php echo number_format($my_max_bid, 2); ?></span>
                    &nbsp;|&nbsp; 
                    Total bids: <?php echo $num_bids; ?>
                </small>
            </div>

            <div class="text-right ml-4" style="min-width: 150px;">
                <div class="mb-1">Current Price</div>
                <div class="h5 mb-2" style="font-family: 'Oswald', sans-serif; color: #fff;">
                    £<?php echo number_format($current_price, 2); ?>
                </div>
                
                <div class="small text-muted mb-2">
                    Ends: <?php echo date_format($end_time, 'j M H:i'); ?>
                </div>

                <?php if ($ended && $winner_id === $user_id): ?>
                    <?php if (!$paid): ?>
                        <a href="pay.php?auction_id=<?php echo $row['auction_id'] ?>" 
                           class="btn btn-sm btn-success btn-block shadow-sm">
                           Pay Now
                        </a>
                    <?php else: ?>
                        <span class="badge badge-success p-2 w-100">PAID <i class="fa fa-check"></i></span>
                    <?php endif; ?>
                <?php elseif (!$ended): ?>
                     <a href="listing.php?item_id=<?php echo $item_id; ?>" class="btn btn-sm btn-outline-light btn-block">
                        View Auction
                     </a>
                <?php endif; ?>

            </div>
        </li>
        <?php endwhile; $result->free(); ?>
    </ul>
    <?php endif; ?>

</div>

<?php include_once("footer.php")?>