<?php
require_once 'utilities.php';
close_expired_auctions();   // 先结算已到期拍卖
include_once 'header.php';
?>
<div class="container">

<h2 class="my-3">My bids</h2>

<?php
  // This page is for showing a user the auctions they've bid on.
  // It will be pretty similar to browse.php, except there is no search bar.
  // This can be started after browse.php is working with a database.
  // Feel free to extract out useful functions from browse.php and put them in
  // the shared "utilities.php" where they can be shared by multiple files.
  
  
  // TODO: Check user's credentials (cookie/session).
  if (!is_logged_in()) {
    echo '<p>You must be logged in to view your bids.</p>';
    include_once 'footer.php';
    exit();
  }

  $user_id = current_user_id();

  // TODO: Perform a query to pull up the auctions they've bidded on.
  
  /* 说明：
   * - a: auctions
   * - i: items
   *
   * We now use two aggregated subqueries on bids:
   *   - all_bids: highest bid and total number of bids for each auction (all users)
   *   - user_bids: highest bid placed by the current user for each auction
   *
   * Then we join them to get:
   *   - current_price  = highest bid among all users for this auction
   *   - my_max_bid     = highest bid of the current user in this auction
   *   - num_bids       = total count of bids in this auction
   */
  $sql = "
    SELECT
      a.auction_id,
      a.item_id,
      a.end_date,
      a.status,
      a.winner_id,
      i.title,
      i.description,
      COALESCE(all_bids.max_bid, a.start_price) AS current_price,
      COALESCE(all_bids.bid_count, 0) AS num_bids,
      user_bids.user_max_bid AS my_max_bid
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    JOIN (
        SELECT
          auction_id,
          MAX(bid_amount) AS max_bid,
          COUNT(*)       AS bid_count
        FROM bids
        GROUP BY auction_id
    ) AS all_bids
      ON all_bids.auction_id = a.auction_id
    JOIN (
        SELECT
          auction_id,
          MAX(bid_amount) AS user_max_bid
        FROM bids
        WHERE buyer_id = ?
        GROUP BY auction_id
    ) AS user_bids
      ON user_bids.auction_id = a.auction_id
    ORDER BY a.end_date DESC
  ";

  // Only one parameter now: the current user's id
  $result = db_query($sql, 'i', [$user_id]);

  // TODO: Loop through results and print them out as list items.
  if (!$result || $result->num_rows === 0): ?>
     <p>You have not placed any bids yet.</p>
  <?php else: ?>

    <ul class="list-group">

    <?php while ($row = $result->fetch_assoc()):
        $item_id       = (int)$row['item_id'];
        $title         = $row['title'];
        $description   = $row['description'];
        $current_price = (float)$row['current_price'];
        $num_bids      = (int)$row['num_bids'];
        $end_time      = new DateTime($row['end_date']);
        $status        = $row['status'];
        $winner_id     = $row['winner_id'] !== null ? (int)$row['winner_id'] : null;
        $my_max_bid    = $row['my_max_bid'] !== null ? (float)$row['my_max_bid'] : null;

        // 计算拍卖是否结束
        $now   = new DateTime();
        $ended = ($now >= $end_time || $status === 'finished' || $status === 'cancelled');

        // Compute result text and outbid alert flag
        $result_text       = '';
        $outbid_highlight  = false; // whether to visually highlight an outbid state

        if (!$ended) {
          // 拍卖还在进行，比较“我自己的最高出价”和“全场最高出价”
          if ($my_max_bid !== null && $my_max_bid >= $current_price) {
            $result_text = 'Currently winning';
          } else {
            $result_text      = 'Outbid';
            $outbid_highlight = true; // mark this auction as outbid
          }
        } else {
          // 拍卖已结束，根据 winner_id 判断是否获胜
          if ($winner_id !== null && $winner_id === $user_id) {
            $result_text = 'You won';
          } else {
            $result_text = 'You lost';
          }
        }
    ?>
      <li class="list-group-item <?php echo $outbid_highlight ? 'list-group-item-warning' : ''; ?>">
        <div class="d-flex justify-content-between">
          <div>
            <h5>
              <a href="listing.php?item_id=<?php echo $item_id; ?>">
                <?php echo htmlspecialchars($title); ?>
              </a>
            </h5>
            <p class="mb-1">
              <?php
                // 简单截断描述，避免太长
                $desc_short = (strlen($description) > 120)
                  ? substr($description, 0, 120) . '...'
                  : $description;
                echo htmlspecialchars($desc_short);
              ?>
            </p>
            <small>
              Bids you placed: <?php echo $num_bids; ?> total bids in this auction
            </small>
          </div>
          <div class="text-right text-nowrap ml-3">
            <div>Current price: £<?php echo number_format($current_price, 2); ?></div>
            <?php if ($my_max_bid !== null): ?>
              <div>Your highest bid: £<?php echo number_format($my_max_bid, 2); ?></div>
            <?php endif; ?>
            <div>
              Ends: <?php echo date_format($end_time, 'j M H:i'); ?>
            </div>
            <div><strong><?php echo htmlspecialchars($result_text); ?></strong></div>
            <?php if ($outbid_highlight): ?>
              <div class="text-danger font-weight-bold">You have been outbid!</div>
            <?php endif; ?>
          </div>
        </div>
      </li>
    <?php endwhile;
      $result->free(); ?>
    </ul>

  <?php endif; ?>

</div>
<?php include_once("footer.php")?>
