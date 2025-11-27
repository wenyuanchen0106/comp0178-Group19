<?php
// pay_result.php
// 作用：处理支付确认表单，向 payments 表插入记录，并重定向。

require_once 'utilities.php';
require_login();

// TODO: 1. 从 $_POST 读取 auction_id, amount, payment_method（可写死为 'Mock' 等）。 
//
// TODO: 2. 再次校验：当前用户是否为该 auction 的 winner。
//         - 如果不是，拒绝支付操作。
//
// TODO: 3. 检查是否已经有支付记录（避免重复支付）。
//
// TODO: 4. INSERT INTO payments (user_id, auction_id, amount, payment_method, status, paid_at)。
//         - status 可以先写 'completed'。
//
// TODO: 5. 设置一条 session 消息提示支付成功，重定向回 mybids.php 或 mylistings.php。

exit;
