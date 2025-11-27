<?php include_once("header.php")?>

<div class="container">

<h2 class="my-3">Seller Statistics</h2>

<?php
  // 检查用户身份（必须已登录且为 seller）
  if (!is_logged_in() || current_user_role() !== 'seller') {
      echo '<div class="alert alert-danger text-center my-4">
              You must be logged in as a seller to view statistics.
            </div>';
      echo '<div class="text-center mb-5">
              <a href="browse.php" class="btn btn-secondary">Back to browse</a>
            </div>';
      include_once("footer.php");
      exit();
  }

  $seller_id = current_user_id();

  // ===============================
  // 统计查询 - 简化版本，直接使用 auctions.seller_id
  // ===============================

  // 1. 总拍卖数
  $sql_total = "SELECT COUNT(*) as total_auctions FROM auctions WHERE seller_id = ?";
  $result_total = db_query($sql_total, "i", [$seller_id]);
  $total_auctions = 0;
  if ($result_total && $row = $result_total->fetch_assoc()) {
      $total_auctions = (int)$row['total_auctions'];
  }

  // 2. 总成交数量（status='finished' AND winner_id IS NOT NULL）
  $sql_sold = "SELECT COUNT(*) as sold_count FROM auctions WHERE seller_id = ? AND status = 'finished' AND winner_id IS NOT NULL";
  $result_sold = db_query($sql_sold, "i", [$seller_id]);
  $sold_count = 0;
  if ($result_sold && $row = $result_sold->fetch_assoc()) {
      $sold_count = (int)$row['sold_count'];
  }

  // 3. 总收入（从 payments 表中计算已完成的支付）
  $sql_revenue = "
    SELECT SUM(p.amount) as total_revenue
    FROM payments p
    JOIN auctions a ON p.auction_id = a.auction_id
    WHERE a.seller_id = ? AND p.status = 'completed'
  ";
  $result_revenue = db_query($sql_revenue, "i", [$seller_id]);
  $total_revenue = 0;
  if ($result_revenue && $row = $result_revenue->fetch_assoc()) {
      $total_revenue = $row['total_revenue'] ? (float)$row['total_revenue'] : 0;
  }

  // 4. 进行中拍卖数（status='active'）
  $sql_active = "SELECT COUNT(*) as active_count FROM auctions WHERE seller_id = ? AND status = 'active'";
  $result_active = db_query($sql_active, "i", [$seller_id]);
  $active_count = 0;
  if ($result_active && $row = $result_active->fetch_assoc()) {
      $active_count = (int)$row['active_count'];
  }

  // 5. 待开始拍卖数（status='pending'）
  $sql_pending = "SELECT COUNT(*) as pending_count FROM auctions WHERE seller_id = ? AND status = 'pending'";
  $result_pending = db_query($sql_pending, "i", [$seller_id]);
  $pending_count = 0;
  if ($result_pending && $row = $result_pending->fetch_assoc()) {
      $pending_count = (int)$row['pending_count'];
  }

  // 6. 已取消数（status='cancelled'）
  $sql_cancelled = "SELECT COUNT(*) as cancelled_count FROM auctions WHERE seller_id = ? AND status = 'cancelled'";
  $result_cancelled = db_query($sql_cancelled, "i", [$seller_id]);
  $cancelled_count = 0;
  if ($result_cancelled && $row = $result_cancelled->fetch_assoc()) {
      $cancelled_count = (int)$row['cancelled_count'];
  }

  // 7. 未售出数（status='finished' AND winner_id IS NULL）
  $sql_unsold = "SELECT COUNT(*) as unsold_count FROM auctions WHERE seller_id = ? AND status = 'finished' AND winner_id IS NULL";
  $result_unsold = db_query($sql_unsold, "i", [$seller_id]);
  $unsold_count = 0;
  if ($result_unsold && $row = $result_unsold->fetch_assoc()) {
      $unsold_count = (int)$row['unsold_count'];
  }

  // 8. 平均成交价（取每个拍卖的最高出价）
  $sql_avg_price = "
    SELECT AVG(max_bid) as avg_price
    FROM (
      SELECT MAX(b.bid_amount) as max_bid
      FROM bids b
      JOIN auctions a ON b.auction_id = a.auction_id
      WHERE a.seller_id = ? AND a.status = 'finished' AND a.winner_id IS NOT NULL
      GROUP BY a.auction_id
    ) as subquery
  ";
  $result_avg = db_query($sql_avg_price, "i", [$seller_id]);
  $avg_price = 0;
  if ($result_avg && $row = $result_avg->fetch_assoc()) {
      $avg_price = $row['avg_price'] ? (float)$row['avg_price'] : 0;
  }

  // 9. 最高成交价
  $sql_max_price = "
    SELECT MAX(b.bid_amount) as max_price
    FROM bids b
    JOIN auctions a ON b.auction_id = a.auction_id
    WHERE a.seller_id = ? AND a.status = 'finished' AND a.winner_id IS NOT NULL
  ";
  $result_max = db_query($sql_max_price, "i", [$seller_id]);
  $max_price = 0;
  if ($result_max && $row = $result_max->fetch_assoc()) {
      $max_price = $row['max_price'] ? (float)$row['max_price'] : 0;
  }

  // 计算成功率
  $success_rate = $total_auctions > 0 ? ($sold_count / $total_auctions) * 100 : 0;
?>

<!-- 统计卡片 -->
<div class="row mt-4">
  <!-- 总拍卖数 -->
  <div class="col-md-3 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Total Auctions</h6>
        <h2 class="card-title"><?= $total_auctions ?></h2>
      </div>
    </div>
  </div>

  <!-- 总成交数 -->
  <div class="col-md-3 mb-3">
    <div class="card text-center bg-success text-white">
      <div class="card-body">
        <h6 class="card-subtitle mb-2">Total Sold</h6>
        <h2 class="card-title"><?= $sold_count ?></h2>
      </div>
    </div>
  </div>

  <!-- 总收入 -->
  <div class="col-md-3 mb-3">
    <div class="card text-center bg-primary text-white">
      <div class="card-body">
        <h6 class="card-subtitle mb-2">Total Revenue</h6>
        <h2 class="card-title">£<?= number_format($total_revenue, 2) ?></h2>
      </div>
    </div>
  </div>

  <!-- 成功率 -->
  <div class="col-md-3 mb-3">
    <div class="card text-center bg-info text-white">
      <div class="card-body">
        <h6 class="card-subtitle mb-2">Success Rate</h6>
        <h2 class="card-title"><?= number_format($success_rate, 1) ?>%</h2>
      </div>
    </div>
  </div>
</div>

<!-- 拍卖状态统计 -->
<div class="row mt-3">
  <!-- 进行中 -->
  <div class="col-md-3 mb-3">
    <div class="card text-center border-primary">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Active Auctions</h6>
        <h3 class="card-title text-primary"><?= $active_count ?></h3>
      </div>
    </div>
  </div>

  <!-- 待开始 -->
  <div class="col-md-3 mb-3">
    <div class="card text-center border-warning">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Pending Auctions</h6>
        <h3 class="card-title text-warning"><?= $pending_count ?></h3>
      </div>
    </div>
  </div>

  <!-- 未售出 -->
  <div class="col-md-3 mb-3">
    <div class="card text-center border-secondary">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Unsold</h6>
        <h3 class="card-title text-secondary"><?= $unsold_count ?></h3>
      </div>
    </div>
  </div>

  <!-- 已取消 -->
  <div class="col-md-3 mb-3">
    <div class="card text-center border-danger">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Cancelled</h6>
        <h3 class="card-title text-danger"><?= $cancelled_count ?></h3>
      </div>
    </div>
  </div>
</div>

<!-- 价格统计 -->
<div class="row mt-3 mb-5">
  <!-- 平均成交价 -->
  <div class="col-md-6 mb-3">
    <div class="card">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Average Sold Price</h6>
        <h3 class="card-title">£<?= number_format($avg_price, 2) ?></h3>
        <p class="text-muted mb-0">Based on <?= $sold_count ?> sold auctions</p>
      </div>
    </div>
  </div>

  <!-- 最高成交价 -->
  <div class="col-md-6 mb-3">
    <div class="card">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Highest Sold Price</h6>
        <h3 class="card-title">£<?= number_format($max_price, 2) ?></h3>
        <p class="text-muted mb-0">Your best sale</p>
      </div>
    </div>
  </div>
</div>

<!-- 操作按钮 -->
<div class="text-center mb-5">
  <a href="mylistings.php" class="btn btn-secondary">Back to My Listings</a>
  <a href="create_auction.php" class="btn btn-primary">Create New Auction</a>
</div>

</div>

<?php include_once("footer.php")?>
