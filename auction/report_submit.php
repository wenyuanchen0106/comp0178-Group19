<?php
// report_submit.php
// 作用：处理举报表单提交，将数据写入 reports 表。

require_once 'utilities.php';

// TODO: 1. 检查用户是否已登录。
//
// TODO: 2. 从 $_POST 读取 auction_id（或 item_id）、description 文本。
//         - 做基本校验：非空，长度限制等。
//
// TODO: 3. 可选：根据 auction_id 查询出 item_id，方便存入 reports(item_id, auction_id)。
//
// TODO: 4. INSERT INTO reports (user_id, auction_id, item_id, description, status='open', created_at=NOW())。
//
// TODO: 5. 设置一条 session 消息提示“Report submitted”。
//         - 然后重定向：
//           a) 回 listing.php?auction_id=xxx，或者
//           b) 回用户一个统一的“举报已提交”页面。

exit;
