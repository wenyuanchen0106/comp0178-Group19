<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utilities.php';

close_expired_auctions();
activate_pending_auctions();

include_once 'header.php';

if (!is_logged_in()) {
    echo '<div class="alert alert-danger text-center my-4">You must be logged in to view your bids.</div>';
    include_once 'footer.php';
    exit();
}

$user_role = current_user_role();
if ($user_role === 'seller') {
    echo '<div class="alert alert-warning text-center my-4">';
    echo '<h4>Access Restricted</h4>';
    echo '<p>Sellers cannot place bids. This page is only available for buyers.</p>';
    echo '<p><a href="mylistings.php" class="btn btn-primary">View My Listings</a></p>';
    echo '</div>';
    include_once 'footer.php';
    exit();
}

$user_id = current_user_id();

$sql = "
    SELECT DISTINCT
        a.auction_id,
        a.item_id,
        a.end_date,
        a.status,
        a.winner_id,
        i.title,
        i.description,
        i.image_path
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    JOIN bids b  ON a.auction_id = b.auction_id
    WHERE b.buyer_id = ?
    ORDER BY a.end_date DESC
";
$result = db_query($sql, 'i', [$user_id]);
?>

<div class="container">
    <h2 class="my-3 text-uppercase" style="font-family: 'Oswald', sans-serif; letter-spacing: 1px;">My bids</h2>

    <?php if (!$result || $result->num_rows === 0): ?>
        <div class="text-center py-5">
            <p class="text-muted mb-4">You have not placed any bids yet.</p>
            <a href="browse.php" class="btn btn-primary btn-lg">Browse Auctions</a>
        </div>
    <?php else: ?>
        <ul class="list-group mb-5" style="border: none;">
            <?php while ($row = $result->fetch_assoc()):
                $auction_id   = (int)$row['auction_id'];
                $item_id      = (int)$row['item_id'];
                $title        = $row['title'];
                $description  = $row['description'];
                $end_time     = new DateTime($row['end_date']);
                $status       = $row['status'];
                $winner_id    = $row['winner_id'] !== null ? (int)$row['winner_id'] : null;

                $image_path_row = $row['image_path'] ?? '';
                if (!empty($image_path_row) && file_exists($image_path_row)) {
                    $img_html = '<img src="' . htmlspecialchars($image_path_row) . '" alt="Item image" style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #333;">';
                } else {
                    $img_html = '<div class="img-placeholder" style="width: 120px; height: 120px; margin: 0;"></div>';
                }

                $sql_stats = "SELECT COUNT(*) AS num_bids, MAX(bid_amount) AS max_bid FROM bids WHERE auction_id = ?";
                $res_stats = db_query($sql_stats, 'i', [$auction_id]);
                $stats_row = $res_stats->fetch_assoc();
                $num_bids  = (int)$stats_row['num_bids'];
                $max_bid   = $stats_row['max_bid'];

                $sql_my = "SELECT MAX(bid_amount) AS my_max_bid FROM bids WHERE auction_id = ? AND buyer_id = ?";
                $res_my   = db_query($sql_my, 'ii', [$auction_id, $user_id]);
                $my_row   = $res_my->fetch_assoc();
                $my_max_bid = $my_row['my_max_bid'] !== null ? (float)$my_row['my_max_bid'] : null;

                $sql_paid = "SELECT 1 FROM payments WHERE auction_id = ? AND user_id = ? AND status = 'completed' LIMIT 1";
                $res_paid = db_query($sql_paid, 'ii', [$auction_id, $user_id]);
                $paid     = ($res_paid && $res_paid->num_rows > 0);

                $sql_start = "SELECT start_price FROM auctions WHERE auction_id = ? LIMIT 1";
                $res_start = db_query($sql_start, 'i', [$auction_id]);
                $start_row = $res_start->fetch_assoc();
                $start_price = (float)$start_row['start_price'];

                $current_price = ($max_bid === null) ? $start_price : (float)$max_bid;

                $now   = new DateTime();
                $ended = ($now >= $end_time || $status === 'finished' || $status === 'closed' || $status === 'paid' || $status === 'cancelled');

                $result_text = '';
                $status_color = '';
                $text_class = '';

                if (!$ended) {
                    if ($my_max_bid !== null && $my_max_bid >= $current_price) {
                        $result_text = 'Currently winning';
                        $status_color = '#28a745';
                        $text_class = 'text-success';
                    } else {
                        $result_text = 'Outbid';
                        $status_color = '#dc3545';
                        $text_class = 'text-danger';
                    }
                } else {
                    if ($winner_id !== null && $winner_id === $user_id) {
                        $result_text = 'You won';
                        $status_color = '#28a745';
                        $text_class = 'text-success';
                    } else {
                        $result_text = 'You lost';
                        $status_color = '#6c757d';
                        $text_class = 'text-muted';
                    }
                }
            ?>
            
            <li class="list-group-item d-flex align-items-center" 
                style="background-color: rgba(28, 28, 30, 0.9); border: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px; border-radius: 4px; border-left: 6px solid <?php echo $status_color; ?>;">
                
                <div class="mr-3">
                    <?php echo $img_html; ?>
                </div>

                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <h5 class="mb-1">
                            <a href="listing.php?item_id=<?php echo $item_id; ?>" class="text-light" style="font-family: 'Oswald', sans-serif; letter-spacing: 0.5px;">
                                <?php echo htmlspecialchars($title); ?>
                            </a>
                        </h5>
                        <span class="<?php echo $text_class; ?> font-weight-bold text-uppercase" style="font-family: 'Oswald', sans-serif;">
                            <?php echo $result_text; ?>
                        </span>
                    </div>

                    <p class="mb-1 text-muted small" style="line-height: 1.4;">
                        <?php
                        $desc_short = (strlen($description) > 120) ? substr($description, 0, 120) . '...' : $description;
                        echo htmlspecialchars($desc_short);
                        ?>
                    </p>
                    
                    <small class="text-light">
                        My Bid: <span style="color: var(--color-accent);">£<?php echo number_format($my_max_bid, 2); ?></span>
                        &nbsp;|&nbsp; 
                        Total bids: <?php echo $num_bids; ?>
                    </small>
                </div>

                <div class="text-right ml-4 text-nowrap" style="min-width: 140px;">
                    <div class="mb-1">Current: £<?php echo number_format($current_price, 2); ?></div>
                    <div class="small text-muted mb-2">
                        Ends: <?php echo date_format($end_time, 'j M H:i'); ?>
                    </div>

                    <?php if ($ended && $winner_id === $user_id): ?>
                        <?php if (!$paid): ?>
                            <a href="pay.php?auction_id=<?php echo $auction_id; ?>" class="btn btn-sm btn-success btn-block">
                                Pay now
                            </a>
                        <?php else: ?>
                            <span class="badge badge-success p-2 w-100">Paid <i class="fa fa-check"></i></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </li>
            <?php endwhile; $result->free(); ?>
        </ul>
    <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
