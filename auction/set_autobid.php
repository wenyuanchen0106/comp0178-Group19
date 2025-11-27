<?php
// set_autobid.php
// 作用：处理 listing.php 页面上的 Auto-bid 提交，写入/更新 autobids 表。

require_once 'utilities.php';

// TODO: 1. 检查用户是否已登录（is_logged_in），否则拒绝。
//
// TODO: 2. 从 $_POST 中读取 auction_id, max_amount, step。
//         - 做基本的校验（非空，数值 >= 0 等）。
//
// TODO: 3. 确认 auction 存在且处于允许自动出价的状态（例如 active）。
//
// TODO: 4. 在 autobids 表中：
//         - 如果该用户对该 auction 已有记录，则 UPDATE。
//         - 否则 INSERT 一条新的自动出价设置。
//
// TODO: 5. 设置一条 session 消息（可选），提示“Auto-bid saved / updated”。
//
// TODO: 6. 重定向回 listing.php 对应的 item：
//         - 可能需要通过 auction_id 查出 item_id，再 header("Location: listing.php?item_id=...");

exit;
