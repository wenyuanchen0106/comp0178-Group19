<?php
require_once 'utilities.php';
close_expired_auctions();      // 详情页打开时也顺手结算一下已到期拍卖
include_once 'header.php';
?>

<?php
  // Get info from the URL:
  //$item_id = $_GET['item_id'];

  // TODO: Use item_id to make a query to the database.
 // Get info from the URL:
  if (!isset($_GET['item_id'])) {
    die('Missing item_id.');
  }
  $item_id = (int)$_GET['item_id'];
  if ($item_id <= 0) {
    die('Invalid item_id.');
  }

  // 1) 用 item_id 找到对应的拍卖 + 物品信息
  //    简单处理：假设一个 item 同时只有一条正在进行/最近的拍卖
  $sql = "
    SELECT 
      a.auction_id,
      a.start_price,
      a.end_date,
      a.status,
      i.title,
      i.description
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    WHERE i.item_id = ?
    ORDER BY a.end_date DESC
    LIMIT 1
  ";
  $result = db_query($sql, 'i', [$item_id]);
  if (!$result || $result->num_rows === 0) {
    die('Auction not found for this item.');
  }
  $row = $result->fetch_assoc();
  $result->free();

  $auction_id  = (int)$row['auction_id'];
  $title       = $row['title'];
  $description = $row['description'];
  $start_price = (float)$row['start_price'];
  $end_time    = new DateTime($row['end_date']);
  $status      = $row['status'];

    // 2) 查 bid 情况：最高出价 + 出价次数
  $sql_bid = "
    SELECT 
      COUNT(*) AS num_bids,
      MAX(bid_amount) AS max_bid
    FROM bids
    WHERE auction_id = ?
  ";
  $result_bid = db_query($sql_bid, 'i', [$auction_id]);
  $row_bid    = $result_bid->fetch_assoc();
  $result_bid->free();

  $num_bids   = (int)$row_bid['num_bids'];
  $max_bid    = $row_bid['max_bid'];

  if ($max_bid === null) {
    // 没有人出价时，当前价 = 起拍价
    $current_price = $start_price;
  } else {
    $current_price = (float)$max_bid;
  }
  // DELETEME: For now, using placeholder data.
  /*$title = "Placeholder title";
  $description = "Description blah blah blah";
  $current_price = 30.50;
  $num_bids = 1;
  $end_time = new DateTime('2020-11-02T00:00:00');
  */

  // TODO: Note: Auctions that have ended may pull a different set of data,
  //       like whether the auction ended in a sale or was cancelled due
  //       to lack of high-enough bids. Or maybe not.
  
  // Calculate time to auction end:
  $now = new DateTime();
  
  if ($now < $end_time) {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
  }
  
  // TODO: If the user has a session, use it to make a query to the database
  //       to determine if the user is already watching this item.
  //       For now, this is hardcoded.
   // 4) 观察列表相关（先保留老师的假数据，之后再接 watchlist 表）
  $has_session = is_logged_in();  // 这里用 utilities 里的函数
  $watching    = false;  
?>


<div class="container">

<div class="row"> <!-- Row #1 with auction title + watch button -->
  <div class="col-sm-8"> <!-- Left col -->
    <h2 class="my-3"><?php echo($title); ?></h2>
  </div>
  <div class="col-sm-4 align-self-center"> <!-- Right col -->
<?php
  /* The following watchlist functionality uses JavaScript, but could
     just as easily use PHP as in other places in the code */
  if ($now < $end_time):
?>
    <div id="watch_nowatch" <?php if ($has_session && $watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
    </div>
    <div id="watch_watching" <?php if (!$has_session || !$watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
      <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">Remove watch</button>
    </div>
<?php endif /* Print nothing otherwise */ ?>
  </div>
</div>

<div class="row"> <!-- Row #2 with auction description + bidding info -->
  <div class="col-sm-8"> <!-- Left col with item info -->

    <div class="itemDescription">
    <?php echo($description); ?>
    </div>

  </div>

  <div class="col-sm-4"> <!-- Right col with bidding info -->

    <p>
<?php if ($now > $end_time): ?>
     This auction ended <?php echo(date_format($end_time, 'j M H:i')) ?>
     <!-- TODO: Print the result of the auction here? -->
<?php else: ?>
     Auction ends <?php echo(date_format($end_time, 'j M H:i') . $time_remaining) ?></p>  
    <p class="lead">Current bid: £<?php echo(number_format($current_price, 2)) ?></p>

    <!-- Bidding form -->
    <form method="POST" action="place_bid.php">
      <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">£</span>
        </div>
	    <input
      type="number"
      class="form-control"
      id="bid"
      name="bid_amount"
      step="0.01"
      min="<?php echo $current_price + 0.01; ?>"
      required
      >
      </div>
      <button type="submit" class="btn btn-primary form-control">Place bid</button>
    </form>
<?php endif ?>

  
  </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->



<?php include_once("footer.php")?>


<script> 
// JavaScript functions: addToWatchlist and removeFromWatchlist.

function addToWatchlist(button) {
  console.log("These print statements are helpful for debugging btw");

  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'add_to_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_nowatch").hide();
          $("#watch_watching").show();
        }
        else {
          var mydiv = document.getElementById("watch_nowatch");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Add to watch failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func

function removeFromWatchlist(button) {
  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'remove_from_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_watching").hide();
          $("#watch_nowatch").show();
        }
        else {
          var mydiv = document.getElementById("watch_watching");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Watch removal failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func
</script>