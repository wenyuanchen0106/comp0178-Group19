<?php
// admin_reports.php
// 作用：管理员查看所有举报列表，并进入处理页面（resolve_report.php）。

require_once 'utilities.php';

// TODO: 1. 检查当前用户是否为管理员 / 有权限查看举报。
//         - 例如 role == 'admin' 或某个特殊的 seller 账号。
//         - 若无权限，则显示错误或重定向。

// TODO: 2. 查询 reports 表：
//         - 可以只查 status='open' 的举报，
//         - 也可以同时显示已处理（status='resolved'）的举报，区别展示。

include_once 'header.php';
?>
<div class="container mt-4">
  <h2>Reports management</h2>

  <!-- TODO: 3. 以表格形式列出举报：report_id, user_id, auction_id, description, status, created_at -->
  <!--      每一行可以放一个按钮/链接：resolve_report.php?report_id=xxx 用于标记为已处理 -->
  <!--      这里只留结构，你后续自己填充 HTML 和 PHP 循环。 -->

</div>
<?php include_once 'footer.php'; ?>
