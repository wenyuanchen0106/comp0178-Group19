<?php
require_once 'utilities.php';

// If cleanup logic for expired auctions exists, run it
if (function_exists('close_expired_auctions')) {
    close_expired_auctions();
}
// Activate pending auctions that have reached their start time
if (function_exists('activate_pending_auctions')) {
    activate_pending_auctions();
}

// Query 10 featured / soon-ending auctions for the homepage
// Only show active auctions that have not yet expired
$sql = "
  SELECT
    a.auction_id,
    a.item_id,
    a.end_date,
    i.title,
    i.description,
    i.image_path,
    COALESCE(MAX(b.bid_amount), a.start_price) AS current_price,
    COUNT(b.bid_id) AS num_bids,
    (SELECT u.name
     FROM bids b2
     JOIN users u ON b2.buyer_id = u.user_id
     WHERE b2.auction_id = a.auction_id
     ORDER BY b2.bid_amount DESC, b2.bid_time ASC
     LIMIT 1) AS current_winner
  FROM auctions a
  JOIN items i ON a.item_id = i.item_id
  LEFT JOIN bids b ON a.auction_id = b.auction_id
  WHERE a.status = 'active'
    AND a.end_date > NOW()
  GROUP BY a.auction_id
  ORDER BY a.end_date ASC
  LIMIT 10
";

$result_index = db_query($sql);

include_once 'header.php';
?>

<div class="jumbotron text-center hero-section" style="margin-top: -10px; border-radius: 0;">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1 class="display-4" style="font-family: 'Impact', sans-serif; letter-spacing: 2px; color: #FFD700; text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);">
        S.H.I.E.L.D. ARMORY
    </h1>
    <p class="lead" style="color: #ddd;">SECURE AUCTION PROTOCOL INITIATED.</p>
    
    <a class="btn btn-primary btn-lg mt-3" href="browse.php" role="button">
      <i class="fa fa-search"></i> ACCESS DATABASE
    </a>
  </div>
</div>
<div class="container">

  <h3 class="mt-4 text-uppercase" style="font-family: 'Oswald', sans-serif;">RECOMMENDED TARGETS</h3>

  <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != true || (isset($_SESSION['account_type']) && $_SESSION['account_type'] != 'buyer')): ?>
    <p class="text-muted mt-2">
      Log in as a buyer to see personalized recommendations here.
    </p>
  <?php else: ?>
    <p class="text-muted mt-2">
      Place a bid on an item to receive personalized recommendations.
    </p>
  <?php endif; ?>

  <h3 class="mt-4 text-uppercase" style="font-family: 'Oswald', sans-serif;">LATEST ARRIVALS</h3>
  
  <ul class="list-group mt-3" style="border: none;">
  <?php
    if (!$result_index || $result_index->num_rows == 0) {
      echo '<li class="list-group-item">No active auctions at the moment.</li>';
    } else {
      while ($row = $result_index->fetch_assoc()) {
        $item_id        = $row['item_id'];
        $title          = $row['title'];
        $description    = $row['description'];
        $current_price  = (float)$row['current_price'];
        $num_bids       = (int)$row['num_bids'];
        $end_date       = new DateTime($row['end_date']);
        $image_path     = $row['image_path'] ?? null;
        $current_winner = $row['current_winner'] ?? null;

        // Use helper function in utilities.php to output listing item
        if (function_exists('print_listing_li')) {
            print_listing_li(
              $item_id,
              $title,
              $description,
              $current_price,
              $num_bids,
              $end_date,
              $image_path,
              $current_winner
            );
        } else {
            // Fallback: simple list item with link
            echo '<li class="list-group-item"><a href="listing.php?item_id=' . $item_id . '">' . $title . '</a></li>';
        }
      }
      $result_index->free();
    }
  ?>
  </ul>
</div>

<?php include_once 'footer.php'; ?>
