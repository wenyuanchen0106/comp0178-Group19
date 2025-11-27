<?php
// report.php
// 作用：展示举报表单页面，用户从 listing.php 点击 “Report this auction” 跳转到这里。

require_once 'utilities.php';
require_login(); // TODO: 确认 utilities 里有此函数，没有的话用 is_logged_in() 自己写判断。

// TODO: 1. 从 $_GET 中获取 auction_id（可选也支持 item_id），校验有效性。
// TODO: 2. 可选：查询 auctions/items 表获取标题，用于在页面上显示“你正在举报哪个拍卖”。

include_once 'header.php';
?>
<div class="container mt-4">
  <h2>Report auction</h2>

  <!-- TODO: 3. 在这里放一个 form，method="POST"，action="report_submit.php" -->
  <!--      表单内容：隐藏的 auction_id，textarea 写 reason/description，一个提交按钮。 -->
  <!--      你可以后续再填 HTML，这里先留空也没关系。 -->

  <!-- 示例（可按需要删掉或后续改写）：
  <form method="POST" action="report_submit.php">
      <input type="hidden" name="auction_id" value="...">
      <div class="mb-3">
          <label for="description" class="form-label">Reason / Description</label>
          <textarea class="form-control" id="description" name="description" rows="4"></textarea>
      </div>
      <button type="submit" class="btn btn-danger">Submit report</button>
  </form>
  -->

</div>
<?php include_once 'footer.php'; ?>
