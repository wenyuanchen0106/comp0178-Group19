<?php
require_once 'utilities.php';
include_once 'header.php';

// 1. 检查登录
if (!is_logged_in()) {
    echo '<div class="alert alert-danger text-center my-4">Please log in to view your watchlist.</div>';
    include_once 'footer.php';
    exit();
}

$user_id = current_user_id();

/* 2. 查询 watchlist 列表 (包含 image_path) */
$sql = "
    SELECT 
        w.auction_id,
        i.item_id,
        i.title, 
        i.image_path,
        i.description,
        a.end_date,  -- 新增：获取截止时间，方便显示
        a.status     -- 新增：获取状态
    FROM watchlist w
    JOIN auctions a ON w.auction_id = a.auction_id
    JOIN items i ON a.item_id = i.item_id
    WHERE w.user_id = ?
    ORDER BY a.end_date ASC
";
$result = db_query($sql, "i", [$user_id]);
?>

<div class="container">
    <h2 class="my-3 text-uppercase" style="font-family: 'Oswald', sans-serif; letter-spacing: 1px;">My Watchlist</h2>

<?php if (!$result || $result->num_rows === 0): ?>

    <div class="text-center py-5">
        <p class="text-muted mb-4">You have no items in your watchlist.</p>
        <a href="browse.php" class="btn btn-primary btn-lg">Browse Auctions</a>
    </div>

<?php else: ?>

   <ul class="list-group mb-5" style="border: none;">

        <?php while ($row = $result->fetch_assoc()):
            $auction_id = (int)$row['auction_id'];
            $item_id    = (int)$row['item_id']; 
            $title      = $row['title'];
            $desc       = $row['description'];
            $end_date   = new DateTime($row['end_date']);
            
            // --- 图片逻辑 ---
            $img_path = $row['image_path'] ?? null;
            $img_html = '';
            if (!empty($img_path) && file_exists("images/" . $img_path)) {
                // 有图
                $img_html = '<img src="images/' . $img_path . '" alt="Item" style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #333;">';
            } else {
                // 无图：占位符
                $img_html = '<div class="img-placeholder" style="width: 120px; height: 120px; margin: 0;"></div>';
            }
        ?>

        <li class="list-group-item d-flex align-items-center" 
            style="background-color: rgba(28, 28, 30, 0.9); border: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px; border-radius: 4px; border-left: 4px solid var(--color-accent);">

            <div class="mr-3">
                <?php echo $img_html; ?>
            </div>

            <div class="flex-grow-1">
                <h5 class="mb-1">
                    <a href="listing.php?item_id=<?= $item_id ?>" class="text-light" style="font-family: 'Oswald', sans-serif; letter-spacing: 0.5px;">
                        <?= htmlspecialchars($title) ?>
                    </a>
                </h5>
                <p class="mb-1 text-muted small" style="line-height: 1.4;">
                    <?php
                    // 描述截断
                    $desc_short = (strlen($desc) > 120) ? substr($desc, 0, 120) . '...' : $desc;
                    echo htmlspecialchars($desc_short);
                    ?>
                </p>
                <small class="text-warning">
                    Ends: <?= $end_date->format('j M H:i') ?>
                </small>
            </div>

            <div class="text-right ml-4" style="min-width: 120px;">
                
                <a href="listing.php?item_id=<?= $item_id ?>" 
                   class="btn btn-sm btn-outline-light btn-block mb-2">
                   View Item
                </a>

                <form method="POST" action="watchlist_funcs.php" style="display:inline;">
                    <input type="hidden" name="functionname" value="remove_from_watchlist">
                    <input type="hidden" name="arguments" value="<?= $item_id ?>">
                    <button class="btn btn-sm btn-outline-danger btn-block" type="submit">
                        <i class="fa fa-trash"></i> Remove
                    </button>
                </form>
            </div>

        </li>

        <?php endwhile; ?>

    </ul>

<?php endif; ?>

</div>

<?php include_once 'footer.php'; ?>