<?php
require_once 'utilities.php';
include_once 'header.php';

// 必须登录
if (!is_logged_in()) {
    echo '<div class="alert alert-danger text-center my-4">Please log in to view your watchlist.</div>';
    include_once 'footer.php';
    exit();
}

$user_id = current_user_id();

/* 查询 watchlist 列表 */
$sql = "
    SELECT w.item_id, i.title, i.description
    FROM watchlist w
    JOIN items i ON w.item_id = i.item_id
    WHERE w.user_id = ?
";
$result = db_query($sql, "i", [$user_id]);
?>

<div class="container">
    <h2 class="my-3">My Watchlist</h2>

<?php if (!$result || $result->num_rows === 0): ?>

    <p>You have no items in your watchlist.</p>

<?php else: ?>

    <ul class="list-group mb-5">

        <?php while ($row = $result->fetch_assoc()):
            $item_id = (int)$row['item_id'];
            $title   = $row['title'];
            $desc    = $row['description'];
        ?>

        <li class="list-group-item d-flex justify-content-between align-items-center">

            <div>
                <h5>
                    <a href="listing.php?item_id=<?= $item_id ?>">
                        <?= htmlspecialchars($title) ?>
                    </a>
                </h5>
                <small><?= htmlspecialchars($desc) ?></small>
            </div>

            <div class="text-right">
                <a href="listing.php?item_id=<?= $item_id ?>" 
                   class="btn btn-sm btn-outline-primary mb-2">
                   View item
                </a>

                <!-- Remove 按钮 -->
                <form method="POST" action="watchlist_funcs.php" style="display:inline;">
                    <input type="hidden" name="functionname" value="remove_from_watchlist">
                    <input type="hidden" name="arguments" value="<?= $item_id ?>">
                    <button class="btn btn-sm btn-danger" type="submit">
                        Remove
                    </button>
                </form>
            </div>

        </li>

        <?php endwhile; ?>

    </ul>

<?php endif; ?>

</div>

<?php include_once 'footer.php'; ?>
