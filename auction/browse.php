<?php
require_once 'utilities.php';
close_expired_auctions();
activate_pending_auctions();
// 先给三个变量一个默认值，避免上面的表单里 echo 未定义变量
$keyword  = isset($_GET['keyword'])   ? trim($_GET['keyword'])   : '';
$category = isset($_GET['cat'])       ? $_GET['cat']             : 'all';
$ordering = isset($_GET['order_by'])  ? $_GET['order_by']        : 'pricelow';

// ========= 新增：从数据库读取所有类别 =========
$categories = [];
$sql_cat = "SELECT category_id, category_name FROM categories ORDER BY category_name";
$result_cat = db_query($sql_cat);
if ($result_cat instanceof mysqli_result) {
    while ($row = $result_cat->fetch_assoc()) {
        $categories[] = $row;
    }
    $result_cat->free();
}

include_once 'header.php'; 

  
?>
 

<div class="container">

<h2 class="my-3">Browse listings</h2>

<div id="searchSpecs">
<!-- When this form is submitted, this PHP page is what processes it.
     Search/sort specs are passed to this page through parameters in the URL
     (GET method of passing data to a page). -->
<form method="get" action="browse.php">
  <div class="row">
    <div class="col-md-5 pr-0">
      <div class="form-group">
        <label for="keyword" class="sr-only">Search keyword:</label>
	    <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text bg-transparent pr-0 text-muted">
              <i class="fa fa-search"></i>
            </span>
          </div>
             <input
                type="text"
                class="form-control border-left-0"
                id="keyword"
                name="keyword"
                placeholder="Search for anything"
                value="<?php echo htmlspecialchars($keyword); ?>"
              >
        </div>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-group">
        <label for="cat" class="sr-only">Search within:</label>
        <select class="form-control" id="cat" name="cat">
          <option value="all" <?php if ($category === 'all') echo 'selected'; ?>>
                All categories
              </option>
              <?php foreach ($categories as $cat_row): ?>
                <option
                  value="<?php echo $cat_row['category_id']; ?>"
                  <?php if ((string)$category === (string)$cat_row['category_id']) echo 'selected'; ?>
                >
                  <?php echo htmlspecialchars($cat_row['category_name']); ?>
                </option>
              <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-inline">
        <label class="mx-2" for="order_by">Sort by:</label>
        <select class="form-control" id="order_by" name="order_by">
              <option value="pricelow" <?php if ($ordering === 'pricelow') echo 'selected'; ?>>
                Price (low to high)
              </option>
              <option value="pricehigh" <?php if ($ordering === 'pricehigh') echo 'selected'; ?>>
                Price (high to low)
              </option>
              <option value="date" <?php if ($ordering === 'date') echo 'selected'; ?>>
                Soonest expiry
              </option>
        </select>
      </div>
    </div>
    <div class="col-md-1 px-0">
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </div>
</form>
</div> <!-- end search specs bar -->


</div>

<?php
  // Retrieve these from the URL
  if (!isset($_GET['keyword'])) {
    // TODO: Define behavior if a keyword has not been specified.
    $keyword = '';  
  }
  else {
    $keyword = trim($_GET['keyword']);
  }

  if (!isset($_GET['cat'])) {
    $category = 'all';
    // TODO: Define behavior if a category has not been specified.
  }
  else {
    $category = $_GET['cat'];
  }
  
  if (!isset($_GET['order_by'])) {
    $ordering = 'pricelow';
    // TODO: Define behavior if an order_by value has not been specified.
  }
  else {
    $ordering = $_GET['order_by'];
  }
  
  if (!isset($_GET['page'])) {
    $curr_page = 1;
  }
  else {
    $curr_page = (int)$_GET['page'];
    if ($curr_page < 1) {
      $curr_page = 1;
    }
  }

  /* TODO: Use above values to construct a query. Use this query to 
     retrieve data from the database. (If there is no form data entered,
     decide on appropriate default value/default query to make. */
  
  /* For the purposes of pagination, it would also be helpful to know the
     total number of results that satisfy the above query */
  // ============= 构造查询条件 =============

  // 基本条件：只显示 active 的拍卖
  $where  = " WHERE a.status = 'active' ";
  $params = [];
  $types  = '';

  // 关键词：在 title 和 description 中模糊搜索
  if ($keyword !== '') {
    $where .= " AND (i.title LIKE ? OR i.description LIKE ?) ";
    $like = '%' . $keyword . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
  }

  // 分类：如果不是 all，就按 category_id 过滤
  if ($category !== 'all' && $category !== '') {
    $where .= " AND i.category_id = ? ";
    $params[] = (int)$category;
    $types   .= 'i';
  }

  // 排序方式
  // pricelow / pricehigh / date
  $order_by_sql = " ORDER BY ";
  switch ($ordering) {
    case 'pricehigh':
      $order_by_sql .= "current_price DESC";
      break;
    case 'date':
      $order_by_sql .= "a.end_date ASC";
      break;
    case 'pricelow':
    default:
      $order_by_sql .= "current_price ASC";
      break;
  }
 // 每页多少条
  $results_per_page = 10;
 // ============= 先算总结果数（用于分页） =============
  $count_sql = "
    SELECT COUNT(DISTINCT a.auction_id) AS total
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    $where
  ";

  $result_count = db_query($count_sql, $types, $params);
  $row_count    = $result_count->fetch_assoc();
  $num_results  = (int)$row_count['total'];
  $result_count->free();

  if ($num_results == 0) {
    $max_page = 1;
  } else {
    $max_page = ceil($num_results / $results_per_page);
  }

  if ($curr_page > $max_page) {
    $curr_page = $max_page;
  }

  $offset = ($curr_page - 1) * $results_per_page;

  // ============= 再查当前这一页的具体数据 =============
  $list_sql = "
    SELECT 
      a.auction_id,
      a.item_id,
      i.title,
      i.image_path,
      i.description,
      COALESCE(MAX(b.bid_amount), a.start_price) AS current_price,
      COUNT(b.bid_id) AS num_bids,
      a.end_date
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    $where
    GROUP BY a.auction_id
    $order_by_sql
    LIMIT ? OFFSET ?
  ";

  // 在原参数后面加上 LIMIT / OFFSET
  $list_params = $params;
  $list_types  = $types . 'ii';
  $list_params[] = $results_per_page;
  $list_params[] = $offset;

  $result_list = db_query($list_sql, $list_types, $list_params);
?>

<div class="container mt-5">

<!-- TODO: If result set is empty, print an informative message. Otherwise... -->

<ul class="list-group">

<!-- TODO: Use a while loop to print a list item for each auction listing
     retrieved from the query -->


<?php
  // Demonstration of what listings will look like using dummy data.
 /* $item_id = "87021";
  $title = "Dummy title";
  $description = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum eget rutrum ipsum. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Phasellus feugiat, ipsum vel egestas elementum, sem mi vestibulum eros, et facilisis dui nisi eget metus. In non elit felis. Ut lacus sem, pulvinar ultricies pretium sed, viverra ac sapien. Vivamus condimentum aliquam rutrum. Phasellus iaculis faucibus pellentesque. Sed sem urna, maximus vitae cursus id, malesuada nec lectus. Vestibulum scelerisque vulputate elit ut laoreet. Praesent vitae orci sed metus varius posuere sagittis non mi.";
  $current_price = 30;
  $num_bids = 1;
  $end_date = new DateTime('2020-09-16T11:00:00');
  
  // This uses a function defined in utilities.php
  print_listing_li($item_id, $title, $description, $current_price, $num_bids, $end_date);
  
  $item_id = "516";
  $title = "Different title";
  $description = "Very short description.";
  $current_price = 13.50;
  $num_bids = 3;
  $end_date = new DateTime('2020-11-02T00:00:00');
  
  print_listing_li($item_id, $title, $description, $current_price, $num_bids, $end_date);
*/
 // 如果没有结果
  if ($num_results == 0) {
    echo '<li class="list-group-item">No results found.</li>';
  }
  else {
    // 用老师给的 print_listing_li()，循环真实数据
    while ($row = $result_list->fetch_assoc()) {
      $item_id       = $row['item_id'];                // 先沿用 item_id 版本
      $title         = $row['title'];
      $description   = $row['description'];
      $current_price = (float)$row['current_price'];
      $num_bids      = (int)$row['num_bids'];
      $end_date      = new DateTime($row['end_date']);

      print_listing_li(
        $item_id,
        $title,
        $description,
        $current_price,
        $num_bids,
        $end_date,
        $row['image_path']
      );
    }
    $result_list->free();
  }
?>

</ul>

<!-- Pagination for results listings -->
<nav aria-label="Search results pages" class="mt-5">
  <ul class="pagination justify-content-center">
  
<?php

  // Copy any currently-set GET variables to the URL.
  $querystring = "";
  foreach ($_GET as $key => $value) {
    if ($key != "page") {
      $querystring .= "$key=$value&amp;";
    }
  }
  
  $high_page_boost = max(3 - $curr_page, 0);
  $low_page_boost = max(2 - ($max_page - $curr_page), 0);
  $low_page = max(1, $curr_page - 2 - $low_page_boost);
  $high_page = min($max_page, $curr_page + 2 + $high_page_boost);
  
  if ($curr_page != 1) {
    echo('
    <li class="page-item">
      <a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page - 1) . '" aria-label="Previous">
        <span aria-hidden="true"><i class="fa fa-arrow-left"></i></span>
        <span class="sr-only">Previous</span>
      </a>
    </li>');
  }
    
  for ($i = $low_page; $i <= $high_page; $i++) {
    if ($i == $curr_page) {
      // Highlight the link
      echo('
    <li class="page-item active">');
    }
    else {
      // Non-highlighted link
      echo('
    <li class="page-item">');
    }
    
    // Do this in any case
    echo('
      <a class="page-link" href="browse.php?' . $querystring . 'page=' . $i . '">' . $i . '</a>
    </li>');
  }
  
  if ($curr_page != $max_page) {
    echo('
    <li class="page-item">
      <a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page + 1) . '" aria-label="Next">
        <span aria-hidden="true"><i class="fa fa-arrow-right"></i></span>
        <span class="sr-only">Next</span>
      </a>
    </li>');
  }
?>

  </ul>
</nav>


</div>



<?php include_once("footer.php")?>