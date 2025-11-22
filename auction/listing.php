<?php
require_once 'utilities.php';
close_expired_auctions();
include_once 'header.php';

// =====================
//   1. 获取 item_id
// =====================
if (!isset($_GET['item_id'])) {
    die('Missing item_id.');
}
$item_id = (int)$_GET['item_id'];
if ($item_id <= 0) {
    die('Invalid item_id.');
}

// =====================
//   2. 查询拍卖 + 商品信息
// =====================
$sql = "
    SELECT 
      a.auction_id,
      a.start_price,
      a.end_date,
      a.status,
      i.title,
      i.description
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    WHERE i.item_id = ?
    ORDER BY a.end_date DESC
    LIMIT 1
";
$result = db_query($sql, 'i', [$item_id]);
if (!$result || $result->num_rows === 0) {
    die('Auction not found for this item.');
}
$row = $result->fetch_assoc();
$auction_id  = (int)$row['auction_id'];
$title       = $row['title'];
$description = $row['description'];
$start_price = (float)$row['start_price'];
$end_time    = new DateTime($row['end_date']);
$status      = $row['status'];

// =====================
//   3. 查询出价信息
// =====================
$sql_bid = "
    SELECT 
      COUNT(*) AS num_bids,
      MAX(bid_amount) AS max_bid
    FROM bids
    WHERE auction_id = ?
";
$result_bid = db_query($sql_bid, 'i', [$auction_id]);
$bid_row = $result_bid->fetch_assoc();
$num_bids = (int)$bid_row['num_bids'];
$max_bid  = $bid_row['max_bid'];

$current_price = ($max_bid === null) ? $start_price : (float)$max_bid;

// =====================
//   4. Watchlist（收藏）状态
// =====================
$has_session = is_logged_in();
$watching = false;

if ($has_session) {
    $user_id = $_SESSION['user_id'];

    $sql_watch = "SELECT * FROM watchlist WHERE user_id = ? AND item_id = ?";
    $result_watch = db_query($sql_watch, "ii", [$user_id, $item_id]);
    $watching = ($result_watch->num_rows > 0);
}

// =====================
//   5. 结束时间计算
// =====================
$now = new DateTime();
if ($now < $end_time) {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
}
?>

<div class="container">

<div class="row">
    <!-- 左侧标题 -->
    <div class="col-sm-8">
        <h2 class="my-3"><?php echo htmlspecialchars($title); ?></h2>
    </div>

    <!-- 右侧 watchlist -->
    <div class="col-sm-4 align-self-center">

<?php if ($has_session && $now < $end_time): ?>

    <div id="watch_nowatch" <?php if ($watching) echo 'style="display:none"'; ?>>
        <button class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
    </div>

    <div id="watch_watching" <?php if (!$watching) echo 'style="display:none"'; ?>>
        <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">Remove watch</button>
    </div>

<?php endif; ?>

    </div>
</div>

<div class="row">
    <!-- 左栏：商品描述 -->
    <div class="col-sm-8">
        <div class="itemDescription">
            <?php echo nl2br(htmlspecialchars($description)); ?>
        </div>
    </div>

    <!-- 右栏：出价信息 -->
    <div class="col-sm-4">

        <?php if ($now > $end_time): ?>

            <p>This auction ended <?php echo date_format($end_time, 'j M H:i'); ?></p>

        <?php else: ?>

            <p>Auction ends <?php echo date_format($end_time, 'j M H:i') . $time_remaining; ?></p>
            <p class="lead">Current bid: £<?php echo number_format($current_price, 2); ?></p>

            <!-- 出价表单 -->
            <form method="POST" action="place_bid.php">
                <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">

                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text">£</span>
                    </div>
                    <input
                        type="number"
                        class="form-control"
                        name="bid_amount"
                        step="0.01"
                        min="<?php echo $current_price + 0.01; ?>"
                        required>
                </div>

                <button type="submit" class="btn btn-primary form-control">Place bid</button>
            </form>

        <?php endif; ?>

    </div>
</div>

<?php include_once "footer.php"; ?>


<!-- ===================== -->
<!--       JS 功能         -->
<!-- ===================== -->
<script>
function addToWatchlist() {
    $.ajax('watchlist_funcs.php', {
        type: "POST",
        data: {
            functionname: 'add_to_watchlist',
            arguments: <?php echo $item_id; ?>
        },
        success: function (response) {
            if (response.trim() === "success") {
                $("#watch_nowatch").hide();
                $("#watch_watching").show();
            } else {
                alert("Add to watchlist failed.");
            }
        }
    });
}

function removeFromWatchlist() {
    $.ajax('watchlist_funcs.php', {
        type: "POST",
        data: {
            functionname: 'remove_from_watchlist',
            arguments: <?php echo $item_id; ?>
        },
        success: function (response) {
            if (response.trim() === "success") {
                $("#watch_watching").hide();
                $("#watch_nowatch").show();
            } else {
                alert("Remove watch failed.");
            }
        }
    });
}
</script>
