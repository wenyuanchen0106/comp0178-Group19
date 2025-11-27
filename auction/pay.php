<?php
// pay.php
// 作用：从 mybids.php 的 “Pay now” 按钮跳转过来，展示支付确认页面。

require_once 'utilities.php';
require_login();

// TODO: 1. 从 $_GET 读取 auction_id，校验有效性。
// TODO: 2. 查询 auctions + bids：
//         - 确认当前用户确实是 winner（winner_id == current_user_id）。
//         - 取得需要支付的金额（最高出价，或其他规则）。
//
// TODO: 3. 可选：检查是否已经在 payments 表中存在记录，避免重复支付。

include_once 'header.php';
?>
<div class="container mt-4">
  <h2>Confirm payment</h2>

  <!-- TODO: 4. 在这里展示拍卖标题、应付金额等信息 -->
  <!-- TODO: 5. 一个确认按钮 form，POST 到 pay_result.php，携带 auction_id、amount 等字段。 -->

</div>
<?php include_once 'footer.php'; ?>
