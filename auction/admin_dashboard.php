<?php
require_once 'utilities.php';
require_login();

// Only admin users can access this page
if ($_SESSION['role_name'] !== 'admin') {
    die("Access denied.");
}

// Admin operations: delete auctions, close auctions, handle reports

// Delete an auction
if (isset($_GET['delete_auction'])) {
    $auction_id = (int)$_GET['delete_auction'];
    db_execute("DELETE FROM auctions WHERE auction_id = ?", "i", [$auction_id]);

    echo "<script>alert('Auction deleted successfully'); window.location='admin_dashboard.php';</script>";
    exit();
}

// Close an auction and mark it as finished
if (isset($_GET['close_auction'])) {
    $auction_id = (int)$_GET['close_auction'];
    db_execute("UPDATE auctions SET status='finished', end_date=NOW() WHERE auction_id = ?", "i", [$auction_id]);

    echo "<script>alert('Auction closed successfully'); window.location='admin_dashboard.php';</script>";
    exit();
}

// Dismiss a report without further action
if (isset($_GET['dismiss_report'])) {
    $report_id = (int)$_GET['dismiss_report'];
    db_execute("UPDATE reports SET status='dismissed' WHERE report_id=?", "i", [$report_id]);

    echo "<script>alert('Report dismissed'); window.location='admin_dashboard.php';</script>";
    exit();
}

// Mark a report as resolved
if (isset($_GET['resolve_report'])) {
    $report_id = (int)$_GET['resolve_report'];
    db_execute("UPDATE reports SET status='resolved' WHERE report_id=?", "i", [$report_id]);

    echo "<script>alert('Report resolved'); window.location='admin_dashboard.php';</script>";
    exit();
}

// Fetch all auctions for the admin table
$auctions = db_fetch_all("
    SELECT a.auction_id, a.status, i.title
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    ORDER BY a.auction_id DESC
");

// Fetch all reports for the admin table
$reports = db_fetch_all("
    SELECT report_id, user_id, auction_id, item_id, description, status, created_at
    FROM reports
    ORDER BY report_id DESC
");

include 'header.php';
?>

<div class="container mt-4">
    <h2>Admin Control Center</h2>
    <hr>

    <!-- Reports table -->
    <h3>📌 Manage Reports</h3>

    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Auction</th>
                <th>Item</th>
                <th>Description</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($reports as $rp): ?>
            <tr>
                <td><?= $rp['report_id'] ?></td>
                <td><?= $rp['user_id'] ?></td>
                <td><?= $rp['auction_id'] ?></td>
                <td><?= $rp['item_id'] ?></td>
                <td><?= htmlspecialchars($rp['description']) ?></td>
                <td><?= $rp['status'] ?></td>
                <td><?= $rp['created_at'] ?></td>

                <td>
                    <a href="?resolve_report=<?= $rp['report_id'] ?>" class="btn btn-success btn-sm">Resolve</a>
                    <a href="?dismiss_report=<?= $rp['report_id'] ?>" class="btn btn-warning btn-sm">Dismiss</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <!-- Auctions table -->
    <h3>📌 Manage Auctions</h3>

    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($auctions as $a): ?>
            <tr>
                <td><?= $a['auction_id'] ?></td>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= $a['status'] ?></td>

                <td>
                    <a href="?close_auction=<?= $a['auction_id'] ?>" class="btn btn-primary btn-sm">Close</a>
                    <a href="?delete_auction=<?= $a['auction_id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>

<?php include 'footer.php'; ?>
