<?php
// report_submit.php
require_once 'utilities.php';
require_login();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<p>Invalid request.</p>");
}

$auction_id  = isset($_POST['auction_id']) ? intval($_POST['auction_id']) : 0;
$item_id     = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$description = trim($_POST['description'] ?? '');

$user_id = $_SESSION['user_id'];

if ($auction_id <= 0 || $item_id <= 0 || $description === '') {
    die("<p>Missing required fields.</p>");
}

// Insert new report record
$sql = "INSERT INTO reports (user_id, auction_id, item_id, description, status, created_at)
        VALUES (?, ?, ?, ?, 'open', NOW())";

db_query($sql, "iiis", [$user_id, $auction_id, $item_id, $description]);

?>
<?php include_once 'header.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm" style="border-radius: 10px;">
        <div class="card-body text-center p-4">

            <h3 class="text-success mb-3">✅ Report submitted successfully!</h3>

            <p class="mb-2">Thank you for helping keep our platform safe.</p>
            <p class="text-muted">
                Your report has been received and will be reviewed by an administrator.
            </p>

            <hr>

            <p>
                <strong>Auction ID:</strong> <?php echo htmlspecialchars($auction_id); ?><br>
                <strong>Item ID:</strong> <?php echo htmlspecialchars($item_id); ?>
            </p>

            <a href="listing.php?item_id=<?php echo urlencode($item_id); ?>"
               class="btn btn-primary mt-3"
               style="padding: 10px 20px; border-radius: 6px;">
                ⬅ Back to item listing
            </a>

        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
