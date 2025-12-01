<?php
require_once 'utilities.php';
require_login();

// Admin check (role_id = 3 means admin)
if ($_SESSION['role_id'] != 3) {
    die("<p class='text-danger'>Access denied: Admins only.</p>");
}

$report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;

if ($report_id <= 0) {
    die("<p class='text-danger'>Invalid report ID.</p>");
}

$sql = "UPDATE reports SET status='resolved' WHERE report_id = ?";
db_query($sql, "i", [$report_id]);

include_once 'header.php';
?>

<div class="container mt-5">
    <div class="card shadow-sm" style="border-radius: 10px;">
        <div class="card-body text-center p-4">
            
            <h3 class="text-success mb-3">🎉 Report Resolved</h3>

            <p class="text-muted mb-2">The report has been marked as <strong>resolved</strong>.</p>

            <a href="admin_reports.php" class="btn btn-primary mt-3" style="padding:10px 20px; border-radius:6px;">
                ⬅ Back to Reports
            </a>

        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>

