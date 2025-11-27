<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utilities.php';
close_expired_auctions();
include_once 'header.php';

if (!isset($_GET['item_id'])) {
    die('Missing item_id.');
}
$item_id = (int)$_GET['item_id'];
if ($item_id <= 0) {
    die('Invalid item_id.');
}

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

$has_session = is_logged_in();
$watching = false;

if ($has_session) {
    $user_id = $_SESSION['user_id'];
    $sql_watch = "SELECT * FROM watchlist WHERE user_id = ? AND auction_id = ?";
    $result_watch = db_query($sql_watch, "ii", [$user_id, $auction_id]);
    $watching = ($result_watch->num_rows > 0);
}

$now = new DateTime();
if ($now < $end_time) {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
}

/**
 * ✅ 新增：最近 5 条出价历史（Bid history）
 */
$sql_history = "
    SELECT 
        b.bid_amount,
        b.bid_time,
        u.name
    FROM bids b
    JOIN users u ON b.buyer_id = u.user_id
    WHERE b.auction_id = ?
    ORDER BY b.bid_time DESC
    LIMIT 5
";
$result_history = db_query($sql_history, 'i', [$auction_id]);
$bid_history = [];
if ($result_history && $result_history->num_rows > 0) {
    while ($h = $result_history->fetch_assoc()) {
        $bid_history[] = $h;
    }
    $result_history->free();
}
?>

<div class="container">

    <div class="row">
        <div class="col-sm-8">
            <h2 class="my-3"><?php echo htmlspecialchars($title); ?></h2>
        </div>

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
    </div> <!-- /row (title + watchlist) -->

    <div class="row">
        <div class="col-sm-8">
            <div class="itemDescription">
                <?php echo nl2br(htmlspecialchars($description)); ?>
            </div>
        </div>

        <div class="col-sm-4">

            <?php if ($now > $end_time): ?>

                <p>This auction ended <?php echo date_format($end_time, 'j M H:i'); ?></p>

            <?php else: ?>

                <p>Auction ends <?php echo date_format($end_time, 'j M H:i') . $time_remaining; ?></p>
                <p class="lead">Current bid: £<?php echo number_format($current_price, 2); ?></p>

                <!-- 普通出价表单 -->
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

                <!-- 自动出价表单（autobid） -->
                <?php if ($has_session): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            Auto-bid
                        </div>
                        <div class="card-body">
                            <form method="POST" action="set_autobid.php">
                                <input type="hidden" name="auction_id"
                                    value="<?php echo $auction_id; ?>">

                                <div class="mb-2">
                                    <label for="maxAmount" class="form-label">
                                        Max amount
                                    </label>
                                    <input type="number"
                                        class="form-control"
                                        id="maxAmount"
                                        name="max_amount"
                                        step="0.01"
                                        min="<?php echo $current_price + 0.01; ?>"
                                        required>
                                </div>

                                <div class="mb-2">
                                    <label for="stepAmount" class="form-label">
                                        Step
                                    </label>
                                    <input type="number"
                                        class="form-control"
                                        id="stepAmount"
                                        name="step"
                                        step="0.01"
                                        min="0.01"
                                        required>
                                </div>

                                <button type="submit" class="btn btn-outline-primary w-100">
                                    Save auto-bid
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mt-3">
                        Log in to set an auto-bid
                    </p>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div> <!-- /row (description + bid + autobid) -->

    <!-- ✅ 新增：最近 5 条出价记录 -->
    <div class="row mt-4">
        <div class="col-sm-8">
            <h5>Recent bids</h5>
            <?php if (empty($bid_history)): ?>
                <p class="text-muted mb-3">No bids have been placed yet.</p>
            <?php else: ?>
                <ul class="list-group mb-3">
                    <?php foreach ($bid_history as $bh): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                £<?php echo number_format((float)$bh['bid_amount'], 2); ?>
                            </span>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($bh['name']); ?>
                                &middot;
                                <?php echo date('j M H:i', strtotime($bh['bid_time'])); ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- 举报按钮 -->
<?php if ($has_session): ?>
<div class="row mt-3 mb-4">
    <div class="col-sm-12">
       <a href="report.php?auction_id=<?php echo urlencode($auction_id); ?>&item_id=<?php echo urlencode($item_id); ?>"
   class="btn btn-outline-danger btn-sm">
    Report this auction
</a>



    </div>
</div>
<?php endif; ?>


</div> <!-- /container -->

<?php include_once "footer.php"; ?>

<script>
function addToWatchlist() {
    $.ajax('watchlist_funcs.php', {
        type: "POST",
        data: {
            functionname: 'add_to_watchlist',
            arguments: <?php echo $auction_id; ?>
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
            arguments: <?php echo $auction_id; ?>
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
