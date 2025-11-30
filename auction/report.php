<?php
require_once 'utilities.php';
require_login();

// Admins cannot report auctions
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) {
    die("<p class='text-danger'>Access denied: This feature is only available to buyers.</p>");
}

$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;
$item_id    = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if ($auction_id <= 0 || $item_id <= 0) {
    die("<p class='text-danger'>Invalid request.</p>");
}

include_once 'header.php';
?>

<div class="container mt-5">
    <div class="card shadow-sm" style="border-radius: 10px;">
        <div class="card-body p-4">

            <h3 class="mb-3">📢 Report Auction</h3>
            <p class="text-muted mb-4">Please describe the issue you found in this auction.</p>

            <form method="POST" action="report_submit.php">

                <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">

                <div class="mb-3">
                    <label for="description" class="form-label">Reason / Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4"
                              placeholder="Describe what is wrong with this auction..." required></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="listing.php?item_id=<?php echo $item_id; ?>" class="btn btn-secondary">
                        ⬅ Back
                    </a>

                    <button type="submit" class="btn btn-danger">
                        Submit Report
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
