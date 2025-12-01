<?php
// remove_auction.php
// Admin remove auction

require_once 'utilities.php';
require_login();

// Check if user is admin
if ($_SESSION['role_id'] != 3) {
    echo "<p style='color:red'>Access denied: Admin only.</p>";
    exit;
}

// Get parameters
$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;
$report_id  = isset($_GET['report_id'])  ? intval($_GET['report_id'])  : 0;

if ($auction_id <= 0) {
    echo "<p style='color:red'>Invalid auction ID.</p>";
    exit;
}

// Check if auction exists
$check_sql = "SELECT auction_id, status FROM auctions WHERE auction_id = ?";
$result    = db_query($check_sql, "i", [$auction_id]);

if ($result->num_rows === 0) {
    echo "<p style='color:red'>Auction not found.</p>";
    exit;
}

$auction = $result->fetch_assoc();

// Check if auction is already removed
if ($auction['status'] === 'removed') {
    header("Location: admin_reports.php?message=already_removed");
    exit;
}

// Update auction status to 'removed'
$update_sql = "UPDATE auctions SET status = 'removed' WHERE auction_id = ?";
db_query($update_sql, "i", [$auction_id]);

// If report_id is provided, mark the report as resolved
if ($report_id > 0) {
    $resolve_sql = "UPDATE reports SET status = 'resolved' WHERE report_id = ?";
    db_query($resolve_sql, "i", [$report_id]);
}

// Optional: send notification to seller
// TODO: add notification logic

include_once 'header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fa fa-check-circle"></i> Auction Removed Successfully</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-success">
                <p class="mb-2"><strong>Auction #<?= $auction_id ?> has been removed from the platform.</strong></p>
                <p class="mb-0">The auction is now marked as 'removed' and will no longer be visible to buyers.</p>
            </div>

            <?php if ($report_id > 0): ?>
                <div class="alert alert-info">
                    <p class="mb-0"><i class="fa fa-info-circle"></i> Report #<?= $report_id ?> has been marked as resolved.</p>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="admin_reports.php" class="btn btn-primary">
                    <i class="fa fa-arrow-left"></i> Back to Reports
                </a>
                <a href="browse.php" class="btn btn-secondary">
                    <i class="fa fa-home"></i> Go to Homepage
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
