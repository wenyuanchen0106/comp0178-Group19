<?php
// report.php
// 用于用户举报拍卖页面。用户从 listing.php 点击 “Report this auction” 跳转到这里。

require_once 'utilities.php';
require_login();   // 要求用户已登录

// ===== 获取 auction_id / item_id =====
$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;
$item_id    = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if ($auction_id <= 0 || $item_id <= 0) {
    echo "<p>Invalid report request.</p>";
    exit;
}

include_once 'header.php';
?>

<div class="container mt-4">
    <h2>Report auction</h2>
    <p>Please describe the issue about this auction.</p>

    <form method="POST" action="report_submit.php" class="mt-3">

        <!-- 隐藏字段：传递必要信息 -->
        <input type="hidden" name="auction_id" value="<?= $auction_id ?>">
        <input type="hidden" name="item_id" value="<?= $item_id ?>">

        <div class="mb-3">
            <label class="form-label" for="description">Reason / Description</label>
            <textarea 
                class="form-control" 
                id="description" 
                name="description" 
                rows="4" 
                required></textarea>
        </div>

        <button type="submit" class="btn btn-danger">Submit report</button>
    </form>
</div>

<?php include_once 'footer.php'; ?>
