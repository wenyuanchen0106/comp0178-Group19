<?php include_once("header.php")?>

<div class="container">

<div class="d-flex justify-content-between align-items-center my-3">
  <h2 class="mb-0">My listings</h2>
  <a href="seller_stats.php" class="btn btn-info">View Statistics</a>
</div>


<?php
  // This page is for showing a user the auction listings they've made.
  // It will be pretty similar to browse.php, except there is no search bar.
  // This can be started after browse.php is working with a database.
  // Feel free to extract out useful functions from browse.php and put them in
  // the shared "utilities.php" where they can be shared by multiple files.
  
  
  // TODO: Check user's credentials (cookie/session).
  
  // TODO: Perform a query to pull up their auctions.
  
  // TODO: Loop through results and print them out as list items.

  // ===============================
  // 1. 检查用户身份（必须已登录且为 seller）
  // ===============================
  if (!is_logged_in() || current_user_role() !== 'seller') {
      echo '<div class="alert alert-danger text-center my-4">
              You must be logged in as a seller to view your listings.
            </div>';
      echo '<div class="text-center mb-5">
              <a href="browse.php" class="btn btn-secondary">Back to browse</a>
            </div>';
      include_once("footer.php");
      exit();
  }

  $seller_id = current_user_id();

  // ===============================
  // 2. 查询当前卖家发布的所有拍卖
  // ===============================
  $sql = "
    SELECT
        i.item_id,
        i.title,
        a.start_price,
        a.end_date,
        a.status,
        a.winner_id,
        COUNT(b.bid_id) as num_bids
    FROM items i
    JOIN auctions a ON i.item_id = a.item_id
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    WHERE i.seller_id = ?
    GROUP BY i.item_id, i.title, a.start_price, a.end_date, a.status, a.winner_id
    ORDER BY a.end_date DESC
  ";

  $result = db_query($sql, "i", [$seller_id]);

  // 分成两组
  $active = [];
  $finished = [];

  if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          if ($row['status'] == 'active' || $row['status'] == 'pending') {
              $active[] = $row;
          } else {
              $finished[] = $row;
          }
      }
  }
?>

<?php if (empty($active) && empty($finished)): ?>

  <p class="my-4">You have not created any auctions yet.</p>
  <a href="create_auction.php" class="btn btn-primary mb-5">+ Create your first auction</a>

<?php else: ?>

  <!-- Active Auctions -->
  <?php if (!empty($active)): ?>
    <h4 class="mt-4 mb-3">Active Auctions</h4>
    <ul class="list-group mb-5">
      <?php foreach ($active as $row):
          $item_id = (int)$row['item_id'];
          $title = $row['title'];
          $start_price = (float)$row['start_price'];
          $num_bids = (int)$row['num_bids'];
          $end_date = new DateTime($row['end_date']);
          $now = new DateTime();

          if ($now > $end_date) {
              $time_remaining = 'Ending soon';
          } else {
              $interval = date_diff($now, $end_date);
              $time_remaining = display_time_remaining($interval) . ' remaining';
          }
      ?>
        <li class="list-group-item">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5>
                <a href="listing.php?item_id=<?= $item_id ?>">
                  <?= htmlspecialchars($title) ?>
                </a>
              </h5>
              <small class="text-muted">
                Ends: <?= $end_date->format('Y-m-d H:i') ?> (<?= $time_remaining ?>)
              </small>
            </div>
            <div class="text-right">
              <div>Start price: £<?= number_format($start_price, 2) ?></div>
              <small class="text-muted"><?= $num_bids ?> bid(s)</small>
              <div class="mt-2">
                <a href="listing.php?item_id=<?= $item_id ?>" class="btn btn-sm btn-outline-primary">View</a>
                <button class="btn btn-sm btn-danger" onclick="endAuction(<?= $item_id ?>)">End Auction</button>
              </div>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <script>
  function endAuction(itemId) {
    if (confirm('Are you sure you want to end this auction now?')) {
      window.location.href = 'end_auction.php?item_id=' + itemId;
    }
  }
  </script>

  <!-- Finished Auctions -->
  <?php if (!empty($finished)): ?>
    <h4 class="mt-4 mb-3">Finished Auctions</h4>
    <ul class="list-group mb-5">
      <?php foreach ($finished as $row):
          $item_id = (int)$row['item_id'];
          $title = $row['title'];
          $start_price = (float)$row['start_price'];
          $num_bids = (int)$row['num_bids'];
          $winner_id = $row['winner_id'];
          $end_date = new DateTime($row['end_date']);
      ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <h5>
              <a href="listing.php?item_id=<?= $item_id ?>">
                <?= htmlspecialchars($title) ?>
              </a>
              <?php if ($winner_id): ?>
                <span class="badge badge-success">Sold</span>
              <?php else: ?>
                <span class="badge badge-secondary">Unsold</span>
              <?php endif; ?>
            </h5>
            <small class="text-muted">
              Ended: <?= $end_date->format('Y-m-d H:i') ?>
            </small>
          </div>
          <div class="text-right">
            <div>Start price: £<?= number_format($start_price, 2) ?></div>
            <small class="text-muted"><?= $num_bids ?> bid(s)</small>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

<?php endif; ?>

</div>

<?php include_once("footer.php")?>