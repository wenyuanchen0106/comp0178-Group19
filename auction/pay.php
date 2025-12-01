<?php
// pay.php
// Shows a payment confirmation screen for the winning bidder before recording payment

// Enable error reporting for debugging during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load shared utilities and session helpers
require_once 'utilities.php';

// Require the user to be logged in; otherwise redirect inside this function
require_login();

// Check that auction_id is provided in the query string
if (!isset($_GET['auction_id'])) {
    // If not provided, return to My Bids page
    redirect('mybids.php');
}

// Sanitize auction_id
$auction_id = (int)$_GET['auction_id'];
if ($auction_id <= 0) {
    // Invalid id, go back
    redirect('mybids.php');
}

// Get the current logged-in user id
$user_id = current_user_id();

// Fetch auction and final price (highest bid or starting price if no bids)
$sql = "
    SELECT 
        a.auction_id,
        a.status,
        a.winner_id,
        a.start_price,
        a.end_date,
        i.title,
        COALESCE(MAX(b.bid_amount), a.start_price) AS current_price
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    WHERE a.auction_id = ?
    GROUP BY a.auction_id, a.status, a.winner_id, a.start_price, a.end_date, i.title
    LIMIT 1
";
$result = db_query($sql, 'i', [$auction_id]);

// If query failed or no auction found, go back
if (!$result || $result->num_rows === 0) {
    redirect('mybids.php');
}

$auction = $result->fetch_assoc();

// Only allow payment if the auction is finished/closed/paid
if (!in_array($auction['status'], ['finished', 'closed', 'paid'], true)) {
    redirect('mybids.php');
}

// Only the winner of this auction is allowed to pay
if ((int)$auction['winner_id'] !== $user_id) {
    redirect('mybids.php');
}

// Check if this user has already completed a payment for this auction
$sql2 = "
    SELECT 1
    FROM payments
    WHERE auction_id = ? AND user_id = ? AND status = 'completed'
    LIMIT 1
";
$res2 = db_query($sql2, 'ii', [$auction_id, $user_id]);

// If a completed payment already exists, redirect with a message
if ($res2 && $res2->num_rows > 0) {
    $_SESSION['success_message'] = 'Payment already recorded.';
    redirect('mybids.php');
}

// Prepare data for the confirmation page
$title = $auction['title'];
$amount = (float)$auction['current_price'];

// Render confirmation page
include_once 'header.php';
?>
<div class="container mt-4">
  <h2>Confirm payment</h2>

  <p>Item: <?php echo htmlspecialchars($title); ?></p>
  <p>Amount to pay: £<?php echo number_format($amount, 2); ?></p>

  <form method="post" action="pay_result.php">
    <!-- Hidden field with auction_id so the result page knows which auction is being paid -->
    <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">

    <div class="mb-3">
      <label for="payment_method" class="form-label">Payment method</label>
      <!-- Simple select for payment method; no real payment gateway integration -->
      <select class="form-select" id="payment_method" name="payment_method" required>
        <option value="card">Card</option>
        <option value="paypal">PayPal</option>
        <option value="bank_transfer">Bank transfer</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Confirm</button>
    <a href="mybids.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
<?php include_once 'footer.php'; ?>
