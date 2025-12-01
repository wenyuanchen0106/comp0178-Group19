<?php
// mylistings.php
// Seller dashboard page listing the seller's own active and finished auctions

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utilities.php';

// Close expired auctions and activate those that have reached their start time
close_expired_auctions();
activate_pending_auctions();

include_once 'header.php';

// 1. Check user role (must be logged in as seller)
if (!is_logged_in() || current_user_role() !== 'seller') {
    echo '<div class="container">
            <div class="alert alert-danger text-center my-4">
              You must be logged in as a seller to view your listings.
            </div>
            <div class="text-center mb-5">
              <a href="browse.php" class="btn btn-secondary">Back to browse</a>
            </div>
          </div>';
    include_once 'footer.php';
    exit();
}

$seller_id = current_user_id();

// 2. Fetch all auctions created by this seller, including basic stats and payment flag
$sql = "
    SELECT
        i.item_id,
        i.image_path,
        i.title,
        a.auction_id,
        a.start_price,
        a.start_date,
        a.end_date,
        a.status,
        a.winner_id,
        COUNT(b.bid_id) AS num_bids,
        MAX(b.bid_amount) AS max_bid,
        EXISTS(
            SELECT 1
            FROM payments p
            WHERE p.auction_id = a.auction_id
              AND p.status = 'completed'
        ) AS is_paid
    FROM items i
    JOIN auctions a ON i.item_id = a.item_id
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    WHERE i.seller_id = ?
    GROUP BY
        i.item_id,
        i.image_path,
        i.title,
        a.auction_id,
        a.start_price,
        a.start_date,
        a.end_date,
        a.status,
        a.winner_id
    ORDER BY a.end_date DESC
";

$result = db_query($sql, "i", [$seller_id]);

$active = [];
$finished = [];

// Split seller auctions into active/pending and finished buckets
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'active' || $row['status'] === 'pending') {
            $active[] = $row;
        } else {
            $finished[] = $row;
        }
    }
}
?>

<div class="container">

<div class="d-flex justify-content-between align-items-center my-3">
  <h2 class="text-uppercase mb-0" style="font-family: 'Oswald', sans-serif; letter-spacing: 1px;">My listings</h2>
  <a href="seller_stats.php" class="btn btn-primary">
    <i class="fas fa-chart-bar"></i> View My Statistics
  </a>
</div>

<?php if (empty($active) && empty($finished)): ?>

  <div class="text-center py-5">
      <p class="my-4 text-muted">You have not created any auctions yet.</p>
      <a href="create_auction.php" class="btn btn-primary btn-lg">+ Create your first auction</a>
  </div>

<?php else: ?>

  <?php if (!empty($active)): ?>
    <h4 class="mt-4 mb-3 text-light" style="font-family: 'Oswald', sans-serif;">ACTIVE AUCTIONS</h4>
    <ul class="list-group mb-5" style="border: none;">
      <?php foreach ($active as $row):
          $item_id     = (int)$row['item_id'];
          $auction_id  = (int)$row['auction_id'];
          $title       = $row['title'];
          $start_price = (float)$row['start_price'];
          $num_bids    = (int)$row['num_bids'];
          $max_bid     = $row['max_bid'] !== null ? (float)$row['max_bid'] : null;
          $start_date  = new DateTime($row['start_date']);
          $end_date    = new DateTime($row['end_date']);
          $now         = new DateTime();
          $status      = $row['status'];

          // Compute current price based on highest bid or starting price
          $current_price = ($max_bid !== null) ? $max_bid : $start_price;

          $img_path = $row['image_path'] ?? null;
          $img_html = '';
          if (!empty($img_path) && file_exists($img_path)) {
              $img_html = '<img src="' . htmlspecialchars($img_path) . '" alt="Item" style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #333;">';
          } else {
              $img_html = '<div class="img-placeholder" style="width: 120px; height: 120px; margin: 0;"></div>';
          }

          // Compute time information (until start for pending, until end for active)
          if ($status === 'pending') {
              if ($now < $start_date) {
                  $interval = date_diff($now, $start_date);
                  $time_info = display_time_remaining($interval) . ' until start';
              } else {
                  $time_info = 'Starting soon';
              }
          } else {
              if ($now > $end_date) {
                  $time_info = 'Ending soon';
              } else {
                  $interval = date_diff($now, $end_date);
                  $time_info = display_time_remaining($interval) . ' remaining';
              }
          }
      ?>
        <li class="list-group-item d-flex align-items-center" 
            style="background-color: rgba(28, 28, 30, 0.9); border: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px; border-radius: 4px; border-left: 4px solid var(--color-primary);">
          
          <div class="mr-3">
              <?php echo $img_html; ?>
          </div>

          <div class="flex-grow-1">
              <h5 class="mb-1">
                <a href="listing.php?item_id=<?php echo $item_id; ?>" class="text-light" style="font-family: 'Oswald', sans-serif; letter-spacing: 0.5px;">
                  <?php echo htmlspecialchars($title); ?>
                </a>
              </h5>
              <div class="text-muted small">
                <?php if ($status === 'pending'): ?>
                  Starts: <?php echo $start_date->format('j M H:i'); ?> <br>
                  <span class="text-info"><?php echo $time_info; ?></span>
                <?php else: ?>
                  Ends: <?php echo $end_date->format('j M H:i'); ?> <br>
                  <span class="text-warning"><?php echo $time_info; ?></span>
                <?php endif; ?>
              </div>
          </div>
          
          <div class="text-right">
              <?php if ($status === 'pending'): ?>
                  <!-- Pending auction: show starting price only -->
                  <div class="mb-2" style="font-family: 'Oswald', sans-serif; font-size: 1.3rem; font-weight: 700; color: var(--color-accent); letter-spacing: 0.5px;">
                      £<?php echo number_format($start_price, 2); ?>
                  </div>
                  <div class="text-muted small mb-2">Starting price</div>
              <?php else: ?>
                  <!-- Active auction: show current price and starting price -->
                  <div class="mb-1" style="font-family: 'Oswald', sans-serif; font-size: 1.3rem; font-weight: 700; color: var(--color-accent); letter-spacing: 0.5px;">
                      £<?php echo number_format($current_price, 2); ?>
                  </div>
                  <div class="text-muted small mb-2">
                      <?php if ($num_bids > 0): ?>
                          Current price (<?php echo $num_bids; ?> bid<?php echo $num_bids > 1 ? 's' : ''; ?>)
                      <?php else: ?>
                          No bids yet
                      <?php endif; ?>
                  </div>
                  <?php if ($num_bids > 0): ?>
                      <div class="text-muted small" style="font-size: 0.85rem;">
                          Start: £<?php echo number_format($start_price, 2); ?>
                      </div>
                  <?php endif; ?>
              <?php endif; ?>
              
              <div class="btn-group-vertical">
                <a href="listing.php?item_id=<?php echo $item_id; ?>" class="btn btn-sm btn-outline-light mb-1">View</a>
                <?php if ($status === 'pending'): ?>
                  <button class="btn btn-sm btn-warning" onclick="cancelAuction(<?php echo $item_id; ?>)">Cancel</button>
                <?php else: ?>
                  <button class="btn btn-sm btn-danger" onclick="endAuction(<?php echo $item_id; ?>)">End Now</button>
                <?php endif; ?>
              </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <script>
  // Helper to end an active auction immediately via redirect
  function endAuction(itemId) {
    if (confirm('Are you sure you want to end this auction now?')) {
      window.location.href = 'end_auction.php?item_id=' + itemId;
    }
  }

  // Helper to cancel a pending auction via redirect
  function cancelAuction(itemId) {
    if (confirm('Are you sure you want to cancel this pending auction?')) {
      window.location.href = 'cancel_auction.php?item_id=' + itemId;
    }
  }
  </script>

  <?php if (!empty($finished)): ?>
    <h4 class="mt-4 mb-3 text-light" style="font-family: 'Oswald', sans-serif;">FINISHED AUCTIONS</h4>
    <ul class="list-group mb-5" style="border: none;">
      <?php foreach ($finished as $row):
          $item_id     = (int)$row['item_id'];
          $auction_id  = (int)$row['auction_id'];
          $title       = $row['title'];
          $start_price = (float)$row['start_price'];
          $num_bids    = (int)$row['num_bids'];
          $winner_id   = $row['winner_id'];
          $end_date    = new DateTime($row['end_date']);
          $max_bid     = $row['max_bid'] !== null ? (float)$row['max_bid'] : null;
          $final_price = $max_bid !== null ? $max_bid : $start_price;
          $is_paid     = (int)$row['is_paid'] === 1;

          $img_path = $row['image_path'] ?? null;
          $img_html = '';
          if (!empty($img_path) && file_exists($img_path)) {
              $img_html = '<img src="' . htmlspecialchars($img_path) . '" alt="Item" style="width: 100px; height: 100px; object-fit: cover; opacity: 0.6;">';
          } else {
              $img_html = '<div class="img-placeholder" style="width: 100px; height: 100px; margin: 0; opacity: 0.6;"></div>';
          }

          // Build status badge based on winner and payment state
          $status_badge = '';
          if ($winner_id) {
              if ($is_paid) {
                  $status_badge = '<span class="badge badge-success ml-2">SOLD (paid)</span>';
              } else {
                  $status_badge = '<span class="badge badge-warning ml-2">SOLD (not paid)</span>';
              }
          } else {
              $status_badge = '<span class="badge badge-secondary ml-2">UNSOLD</span>';
          }
      ?>
        <li class="list-group-item d-flex align-items-center" 
            style="background-color: #1a1a1a; border: 1px solid #333; margin-bottom: 10px; border-radius: 4px; border-left: 4px solid #555;">
          
          <div class="mr-3">
              <?php echo $img_html; ?>
          </div>

          <div class="flex-grow-1">
            <h5 class="mb-1">
              <a href="listing.php?item_id=<?php echo $item_id; ?>" class="text-muted" style="text-decoration: line-through;">
                <?php echo htmlspecialchars($title); ?>
              </a>
              <?php echo $status_badge; ?>
            </h5>
            <small class="text-muted">
              Ended: <?php echo $end_date->format('j M Y'); ?>
            </small>
          </div>

          <div class="text-right text-muted">
            <div>Final price: £<?php echo number_format($final_price, 2); ?></div>
            <small><?php echo $num_bids; ?> bid(s)</small>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

<?php endif; ?>

</div>

<?php include_once 'footer.php'; ?>
