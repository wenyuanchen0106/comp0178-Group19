<?php
// resolve_report.php
// 作用：管理员点击后，将某条举报的 status 更新为 'resolved'。

require_once 'utilities.php';

// TODO: 1. 检查当前用户是否为管理员，有权限处理举报。
//
// TODO: 2. 从 $_GET 或 $_POST 获取 report_id，并校验为整数。
// TODO: 3. UPDATE reports SET status='resolved' WHERE report_id = ?。
// TODO: 4. 可选：记录处理时间、处理人等字段（如果你在表里加了这些列）。
//
// TODO: 5. 设置一条 session 消息（例如 Resolved successfully），然后重定向回 admin_reports.php。

exit;
