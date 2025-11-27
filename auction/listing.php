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
      i.image_path,
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

<div class="container mt-4">

  <div class="row">
    
    <div class="col-md-6 mb-4">
      <?php 
        $img_path = $row['image_path'] ?? null; 
        if (!empty($img_path) && file_exists("images/" . $img_path)) {
            // 有图：显示大图
            echo '<div style="border: 2px solid var(--color-accent); border-radius: 6px; overflow: hidden; box-shadow: 0 0 30px rgba(227, 0, 34, 0.2);">';
            echo '  <img src="images/' . $img_path . '" alt="Item Image" style="width: 100%; height: 500px; object-fit: cover;">';
            echo '</div>';
        } else {
            // 没图：显示占位符
            echo '<div class="img-placeholder-lg" style="height: 500px;"></div>';
        }
      ?>
    </div>

    <div class="col-md-6">
      
      <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2" style="border-color: var(--color-primary) !important;">
          <h2 class="text-uppercase m-0" style="font-family: 'Oswald', sans-serif; color: #fff; letter-spacing: 1px;">
            <?php echo htmlspecialchars($title); ?>
          </h2>
          <div class="ml-3">
            <?php if ($has_session && $now < $end_time): ?>
                <div id="watch_nowatch" <?php if ($watching) echo 'style="display:none"'; ?>>
                    <button class="btn btn-outline-warning btn-sm" onclick="addToWatchlist()">
                        <i class="fa fa-star-o"></i> Watch
                    </button>
                </div>
                <div id="watch_watching" <?php if (!$watching) echo 'style="display:none"'; ?>>
                    <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">X</button>
                </div>
            <?php endif; ?>
          </div>
      </div>

      <div class="item-description p-3 mb-4" style="background: rgba(255,255,255,0.05); border-left: 4px solid #444; border-radius: 4px;">
        <h5 class="text-muted mb-2" style="font-size: 0.9rem; letter-spacing: 1px;">ITEM DESCRIPTION_</h5>
        <div style="color: #ddd; font-size: 1.05rem; line-height: 1.6;">
          <?php echo nl2br(htmlspecialchars($description)); ?>
        </div>
      </div>

      <div class="bidding-zone p-4" style="background-color: var(--color-bg-secondary); border: 1px solid #333; border-radius: 6px;">
          
          <?php if ($now > $end_time): ?>
            <div class="alert alert-dark border-danger text-center font-weight-bold" style="color: #ff6b6b;">
                This auction ended on <?php echo date_format($end_time, 'j M H:i'); ?>
            </div>
          <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted text-uppercase small">Time Remaining</span>
                <span class="font-weight-bold" style="color: var(--color-primary);"><?php echo $time_remaining; ?></span>
            </div>

            <div class="text-center mb-4">
                <p class="lead mb-0 text-muted" style="font-size: 0.8rem; letter-spacing: 2px;">CURRENT BID</p>
                <h1 style="color: var(--color-accent); font-family: 'Oswald', sans-serif; font-size: 3rem; margin: 0;">
                    £<?php echo number_format($current_price, 2); ?>
                </h1>
                <small class="text-muted">Ends: <?php echo date_format($end_time, 'j M H:i'); ?></small>
            </div>

            <form method="POST" action="place_bid.php" class="mb-3">
                <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">
                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0" style="background: #333; color: var(--color-accent);">£</span>
                    </div>
                    <input type="number" class="form-control" name="bid_amount" 
                           style="background: #222; color: #fff; border: 1px solid #444;"
                           step="0.01" min="<?php echo $current_price + 0.01; ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block" style="font-family: 'Oswald', sans-serif; letter-spacing: 1px;">
                    PLACE BID
                </button>
            </form>

            <?php if ($has_session): ?>
                <div class="accordion" id="accordionAutoBid">
                    <div class="card bg-transparent border-0">
                        <div class="card-header p-0 bg-transparent border-0 text-center" id="headingOne">
                            <button class="btn btn-link text-muted text-decoration-none small" type="button" data-toggle="collapse" data-target="#collapseAutoBid">
                                <i class="fa fa-cogs"></i> Configure Auto-bid
                            </button>
                        </div>

                        <div id="collapseAutoBid" class="collapse" data-parent="#accordionAutoBid">
                            <div class="card-body p-3 mt-2" style="border: 1px dashed #555; border-radius: 4px; background: rgba(0,0,0,0.2);">
                                <form method="POST" action="set_autobid.php">
                                    <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="small text-muted">Max Amount</label>
                                                <input type="number" class="form-control form-control-sm bg-dark text-light border-secondary" name="max_amount" step="0.01" min="<?php echo $current_price + 0.01; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="small text-muted">Step</label>
                                                <input type="number" class="form-control form-control-sm bg-dark text-light border-secondary" name="step" step="0.01" min="0.01" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-outline-secondary btn-sm btn-block mt-2">Activate Auto-bid</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted small text-center mt-2">Log in to enable auto-bid</p>
            <?php endif; ?>

          <?php endif; ?>
      </div> </div>
  </div>

  <div class="row mt-5">
    
    <div class="col-md-8">
        <h4 class="text-uppercase mb-3" style="font-family: 'Oswald', sans-serif; color: #aaa; border-bottom: 1px solid #333; padding-bottom: 10px;">
            <i class="fa fa-history"></i> Recent Bids Log
        </h4>
        
        <?php if (empty($bid_history)): ?>
            <p class="text-muted font-italic">No bids recorded in the database.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($bid_history as $bh): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" 
                        style="background-color: var(--color-bg-secondary); border-color: #333; color: #ccc;">
                        
                        <span style="font-family: 'Oswald', sans-serif; font-size: 1.1rem; color: var(--color-accent);">
                            £<?php echo number_format((float)$bh['bid_amount'], 2); ?>
                        </span>
                        
                        <div class="text-right">
                            <span class="font-weight-bold text-light"><?php echo htmlspecialchars($bh['name']); ?></span>
                            <br>
                            <small class="text-muted" style="font-family: monospace;">
                                <?php echo date('Y-m-d H:i:s', strtotime($bh['bid_time'])); ?>
                            </small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="col-md-4 text-right">
        <?php if ($has_session): ?>
            <div class="mt-5 pt-3"> <p class="text-muted small mb-2">Is there an issue with this item?</p>
                <a href="report.php?auction_id=<?php echo urlencode($auction_id); ?>" 
                   class="btn btn-outline-danger btn-block" 
                   style="border-style: dashed;">
                   <i class="fa fa-flag"></i> REPORT AUCTION
                </a>
            </div>
        <?php endif; ?>
    </div>

  </div>

</div> ```



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
