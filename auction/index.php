<?php
  // For now, index.php just redirects to browse.php, but you can change this
  // if you like.
 require_once 'utilities.php';
close_expired_auctions();   

 // TEXT CODE
 // require_once 'utilities.php';

//$conn = get_db();
//if ($conn) {
//    echo "DB OK<br>";
//}

// 取一些正在进行的拍卖（active），按结束时间最近排在前面
$sql = "
  SELECT 
    a.auction_id,
    a.item_id,
    a.end_date,
    i.title,
    i.description,
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
//header("Location: browse.php");
?>

<div class="container">
  <h2 class="my-3">Welcome to the Auction Site</h2>

  <p>
    <a href="browse.php" class="btn btn-primary">Browse all listings</a>
  </p>

  <ul class="list-group mt-4">
  <?php
    if (!$result_index || $result_index->num_rows == 0) {
      echo '<li class="list-group-item">No active auctions at the moment.</li>';
    } else {
      while ($row = $result_index->fetch_assoc()) {
        $item_id       = $row['item_id'];                // 仍然走 item_id
        $title         = $row['title'];
        $description   = $row['description'];
        $current_price = (float)$row['current_price'];
        $num_bids      = (int)$row['num_bids'];
        $end_date      = new DateTime($row['end_date']);

        // 用老师给的工具函数渲染每一条
        print_listing_li(
          $item_id,
          $title,
          $description,
          $current_price,
          $num_bids,
          $end_date
        );
      }
      $result_index->free();
    }
  ?>
  </ul>
</div>

<?php include_once 'footer.php'; ?>