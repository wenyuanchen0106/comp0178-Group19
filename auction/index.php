<?php
require_once 'utilities.php';

// 如果有清理过期拍卖的逻辑
if(function_exists('close_expired_auctions')) {
    close_expired_auctions();
}
// 激活已到开始时间的pending拍卖
if(function_exists('activate_pending_auctions')) {
    activate_pending_auctions();
}

// 查询首页展示的 10 个热门/即将结束的拍卖
// ✅ 修正 1: 加上了 i.image_path
$sql = "
  SELECT 
    a.auction_id,
    a.item_id,
    a.end_date,
    i.title,
    i.description,
    i.image_path,  /* <--- 必须加上这一行！ */
    COALESCE(MAX(b.bid_amount), a.start_price) AS current_price,
    COUNT(b.bid_id) AS num_bids
  FROM auctions a
  JOIN items i ON a.item_id = i.item_id
  LEFT JOIN bids b ON a.auction_id = b.auction_id
  WHERE a.status = 'active'
  GROUP BY a.auction_id
  ORDER BY a.end_date ASC
  LIMIT 10
";

$result_index = db_query($sql);

include_once 'header.php';
?>

<div class="jumbotron text-center hero-section" style="margin-top: -10px; border-radius: 0;">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1 class="display-4" style="font-family: 'Impact', sans-serif; letter-spacing: 2px; color: #FFD700; text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);">
        S.H.I.E.L.D. ARMORY
    </h1>
    <p class="lead" style="color: #ddd;">SECURE AUCTION PROTOCOL INITIATED.</p>
    
    <a class="btn btn-primary btn-lg mt-3" href="browse.php" role="button">
      <i class="fa fa-search"></i> ACCESS DATABASE
    </a>
  </div>
</div>
<div class="container">

  <h3 class="mt-4 text-uppercase" style="font-family: 'Oswald', sans-serif;">RECOMMENDED TARGETS</h3>

  <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != true || (isset($_SESSION['account_type']) && $_SESSION['account_type'] != 'buyer')): ?>
    <p class="text-muted mt-2">
      Log in as a buyer to see personalized recommendations here.
    </p>
  <?php else: ?>
    <p class="text-muted mt-2">
      Place a bid on an item to receive personalized recommendations.
    </p>
  <?php endif; ?>

  <h3 class="mt-4 text-uppercase" style="font-family: 'Oswald', sans-serif;">LATEST ARRIVALS</h3>
  
  <ul class="list-group mt-3" style="border: none;">
  <?php
    if (!$result_index || $result_index->num_rows == 0) {
      echo '<li class="list-group-item">No active auctions at the moment.</li>';
    } else {
      while ($row = $result_index->fetch_assoc()) {
        $item_id       = $row['item_id'];
        $title         = $row['title'];
        $description   = $row['description'];
        $current_price = (float)$row['current_price'];
        $num_bids      = (int)$row['num_bids'];
        $end_date      = new DateTime($row['end_date']);
        
        // ✅ 修正 2: 顺便加上图片变量，确保万无一失
        $image_path    = $row['image_path'] ?? null;

        // 这里调用 utilities.php 里的函数输出列表项
        if(function_exists('print_listing_li')) {
            print_listing_li(
              $item_id,
              $title,
              $description,
              $current_price,
              $num_bids,
              $end_date,
              $image_path // <--- 现在这里有值了，不会报错了
            );
        } else {
            // 备用方案
            echo '<li class="list-group-item"><a href="listing.php?item_id='.$item_id.'">'.$title.'</a></li>';
        }
      }
      $result_index->free();
    }
  ?>
  </ul>
</div>

<?php include_once 'footer.php'; ?>