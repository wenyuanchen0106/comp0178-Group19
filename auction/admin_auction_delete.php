<?php
require_once 'utilities.php';
require_login();
if ($_SESSION['role_id'] != 3) die("Access denied.");

$id = intval($_GET['id']);
db_execute("DELETE FROM auctions WHERE auction_id=?", "i", [$id]);

header("Location: admin_manage_auctions.php");
exit;
