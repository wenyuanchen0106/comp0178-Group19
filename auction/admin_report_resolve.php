<?php
require_once 'utilities.php';
require_login();
if ($_SESSION['role_id'] != 3) die("Access denied.");

$id = intval($_GET['id']);

db_execute("UPDATE reports SET status='resolved' WHERE report_id = ?", "i", [$id]);

header("Location: admin_review_reports.php");
exit;
