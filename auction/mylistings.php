<?php include_once("header.php")?>

<div class="container">

<h2 class="my-3">My listings</h2>


<?php
  // This page is for showing a user the auction listings they've made.
  // It will be pretty similar to browse.php, except there is no search bar.
  // This can be started after browse.php is working with a database.
  // Feel free to extract out useful functions from browse.php and put them in
  // the shared "utilities.php" where they can be shared by multiple files.
  
  
  // TODO: Check user's credentials (cookie/session).
  
  // TODO: Perform a query to pull up their auctions.
  
  // TODO: Loop through results and print them out as list items.

  // ===============================
  // 1. 检查用户身份（必须已登录且为 seller）
  // ===============================
  if (!is_logged_in() || current_user_role() !== 'seller') {
      echo '<div class="alert alert-danger text-center my-4">
              You must be logged in as a seller to view your listings.
            </div>';
      echo '<div class="text-center mb-5">
              <a href="browse.php" class="btn btn-secondary">Back to browse</a>
            </div>';
      include_once("footer.php");
      exit();
  }

  $seller_id = current_user_id();

  // ===============================
  // 2. 查询当前卖家发布的所有拍卖
  //    （按结束时间排序）
  // 假设结构：
  //   items(item_id, title, description, category_id, seller_id, ...)
  //   auctions(auction_id, item_id, start_price, reserve_price, start_date, end_date, ...)
  // ===============================
  $sql = "
    SELECT 
        i.item_id,
        i.title,
        a.start_price,
        a.end_date
    FROM items i
    JOIN auctions a ON i.item_id = a.item_id
    WHERE i.seller_id = ?
    ORDER BY a.end_date DESC
  ";

  $result = db_query($sql, "i", [$seller_id]);
?>

<?php if (!$result || $result->num_rows === 0): ?>

  <p class="my-4">You have not created any auctions yet.</p>
  <a href="create_auction.php" class="btn btn-primary mb-5">+ Create your first auction</a>

<?php else: ?>

  <ul class="list-group mb-5">

    <?php while ($row = $result->fetch_assoc()): 
        $item_id      = (int)$row['item_id'];
        $title        = $row['title'];
        $start_price  = (float)$row['start_price'];
        $end_date     = new DateTime($row['end_date']);
        $now          = new DateTime();
        $ended        = ($now > $end_date);
    ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <h5>
            <a href="listing.php?item_id=<?= htmlspecialchars($item_id) ?>">
              <?= htmlspecialchars($title) ?>
            </a>
          </h5>
          <small>
            <?php if ($ended): ?>
              <span class="text-danger">Ended: <?= $end_date->format('Y-m-d H:i') ?></span>
            <?php else: ?>
              Ends: <?= $end_date->format('Y-m-d H:i') ?>
            <?php endif; ?>
          </small>
        </div>
        <div class="text-right">
          <div>Start price: £<?= number_format($start_price, 2) ?></div>
          <a href="listing.php?item_id=<?= htmlspecialchars($item_id) ?>" class="btn btn-sm btn-outline-primary mt-2">
            View
          </a>
        </div>
      </li>
    <?php endwhile; ?>

  </ul>

<?php endif; ?>

</div>

<?php include_once("footer.php")?>