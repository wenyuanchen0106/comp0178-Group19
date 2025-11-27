<?php
// Show all errors during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utilities.php';

// Close expired auctions before showing bids
close_expired_auctions();

// Include page header
include_once 'header.php';

// Check login
if (!is_logged_in()) {
    echo '<p>You must be logged in to view your bids.</p>';
    include_once 'footer.php';
    exit();
}

// Current user id
$user_id = current_user_id();

/*
 * Step 1: find all distinct auctions where this user has placed at least one bid
 */
$sql = "
    SELECT DISTINCT
        a.auction_id,
        a.item_id,
        a.end_date,
        a.status,
        a.winner_id,
        i.title,
        i.description
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    JOIN bids b  ON a.auction_id = b.auction_id
    WHERE b.buyer_id = ?
    ORDER BY a.end_date DESC
";
$result = db_query($sql, 'i', [$user_id]);
?>

<div class="container">
    <h2 class="my-3">My bids</h2>

    <?php if (!$result || $result->num_rows === 0): ?>
        <p>You have not placed any bids yet.</p>
    <?php else: ?>
        <ul class="list-group">
            <?php while ($row = $result->fetch_assoc()):
                $auction_id   = (int)$row['auction_id'];
                $item_id      = (int)$row['item_id'];
                $title        = $row['title'];
                $description  = $row['description'];
                $end_time     = new DateTime($row['end_date']);
                $status       = $row['status'];
                $winner_id    = $row['winner_id'] !== null ? (int)$row['winner_id'] : null;

                /*
                 * Step 2: compute current_price and num_bids for this auction
                 */
                $sql_stats = "
                    SELECT
                        COUNT(*) AS num_bids,
                        MAX(bid_amount) AS max_bid
                    FROM bids
                    WHERE auction_id = ?
                ";
                $res_stats = db_query($sql_stats, 'i', [$auction_id]);
                $stats_row = $res_stats->fetch_assoc();
                $num_bids  = (int)$stats_row['num_bids'];
                $max_bid   = $stats_row['max_bid'];

                /*
                 * Step 3: compute my_max_bid in this auction
                 */
                $sql_my = "
                    SELECT
                        MAX(bid_amount) AS my_max_bid
                    FROM bids
                    WHERE auction_id = ? AND buyer_id = ?
                ";
                $res_my   = db_query($sql_my, 'ii', [$auction_id, $user_id]);
                $my_row   = $res_my->fetch_assoc();
                $my_max_bid = $my_row['my_max_bid'] !== null ? (float)$my_row['my_max_bid'] : null;

                /*
                 * Step 4: check payment status in payments table
                 */
                $sql_paid = "
                    SELECT 1
                    FROM payments
                    WHERE auction_id = ? AND user_id = ? AND status = 'completed'
                    LIMIT 1
                ";
                $res_paid = db_query($sql_paid, 'ii', [$auction_id, $user_id]);
                $paid     = ($res_paid && $res_paid->num_rows > 0);

                /*
                 * Step 5: derive current_price from max_bid and start_price
                 *         we need start_price from auctions again
                 */
                $sql_start = "SELECT start_price FROM auctions WHERE auction_id = ? LIMIT 1";
                $res_start = db_query($sql_start, 'i', [$auction_id]);
                $start_row = $res_start->fetch_assoc();
                $start_price = (float)$start_row['start_price'];

                $current_price = ($max_bid === null) ? $start_price : (float)$max_bid;

                // Determine whether the auction is ended
                $now   = new DateTime();
                $ended = (
                    $now >= $end_time ||
                    $status === 'finished' ||
                    $status === 'closed' ||
                    $status === 'paid' ||
                    $status === 'cancelled'
                );

                // Build result text
                $result_text = '';
                if (!$ended) {
                    if ($my_max_bid !== null && $my_max_bid >= $current_price) {
                        $result_text = 'Currently winning';
                    } else {
                        $result_text = 'Outbid';
                    }
                } else {
                    if ($winner_id !== null && $winner_id === $user_id) {
                        $result_text = 'You won';
                    } else {
                        $result_text = 'You lost';
                    }
                }
            ?>
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>
                            <a href="listing.php?item_id=<?php echo $item_id; ?>">
                                <?php echo htmlspecialchars($title); ?>
                            </a>
                        </h5>
                        <p class="mb-1">
                            <?php
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
                        <div>Ends: <?php echo date_format($end_time, 'j M H:i'); ?></div>

                        <div>
                          <?php
                              $color = 'text-secondary';
                              if ($result_text === 'Outbid') {
                                  $color = 'text-danger';
                              } elseif ($result_text === 'Currently winning' || $result_text === 'You won') {
                                  $color = 'text-success';
                              }
                          ?>
                          <strong class="<?php echo $color; ?>">
                              <?php echo htmlspecialchars($result_text); ?>
                          </strong>
                        </div>

                        <?php if ($ended && $winner_id === $user_id): ?>
                            <?php if (!$paid): ?>
                                <a href="pay.php?auction_id=<?php echo $auction_id; ?>"
                                   class="btn btn-sm btn-success mt-2">
                                    Pay now
                                </a>
                            <?php else: ?>
                                <span class="badge bg-success mt-2">Paid</span>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </li>
            <?php endwhile; $result->free(); ?>
        </ul>
    <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
