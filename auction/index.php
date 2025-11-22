<?php
require_once 'utilities.php';
close_expired_auctions();

// Query 10 active auctions for homepage preview
$sql = "
  SELECT 
    a.auction_id,
    a.item_id,
    a.end_date,
    i.title,
    i.description,
    COALESCE(MAX(b.bid_amount), a.start_price) AS current_price,
    COUNT(b.bid_id) AS num_bids
  FROM auctions a
  JOIN items i ON a.item_id = i.item_id
  LEFT JOIN bids b ON a.auction_id = b.auction_id
  WHERE a.status = 'active'
  GROUP BY a.auction_id
  ORDER BY a.end_date ASC
  LIMIT 10
";

$result_index = db_query($sql);

include_once 'header.php';
?>

<div class="container">
  <h2 class="my-3">Welcome to the Auction Site</h2>

  <p>
    <a href="browse.php" class="btn btn-primary">Browse all listings</a>
  </p>

  <!-- ===== Recommended section ===== -->
  <h3 class="mt-4">Recommended for you</h3>

  <?php if (!is_logged_in() || current_user_role() !== 'buyer'): ?>
    <p class="text-muted mt-2">
      Log in as a buyer to see personalized recommendations here.
    </p>
  <?php else: ?>
    <p class="text-muted mt-2">
      Place a bid on an item to receive personalized recommendations.
    </p>
  <?php endif; ?>

  <!-- ===== Active auctions preview ===== -->
  <ul class="list-group mt-4">
  <?php
    if (!$result_index || $result_index->num_rows == 0) {
      echo '<li class="list-group-item">No active auctions at the moment.</li>';
    } else {
      while ($row = $result_index->fetch_assoc()) {
        $item_id       = $row['item_id'];
        $title         = $row['title'];
        $description   = $row['description'];
        $current_price = (float)$row['current_price'];
        $num_bids      = (int)$row['num_bids'];
        $end_date      = new DateTime($row['end_date']);

        print_listing_li(
          $item_id,
          $title,
          $description,
          $current_price,
          $num_bids,
          $end_date
        );
      }
      $result_index->free();
    }
  ?>
  </ul>
</div>

<?php include_once 'footer.php'; ?>
