<?php
require_once 'utilities.php';
close_expired_auctions();
activate_pending_auctions();

// Initialize filter variables from GET for the search form
$keyword  = isset($_GET['keyword'])   ? trim($_GET['keyword'])   : '';
$category = isset($_GET['cat'])       ? $_GET['cat']             : 'all';
$ordering = isset($_GET['order_by'])  ? $_GET['order_by']        : 'pricelow';

// Load all categories for the dropdown
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
// Retrieve filters again (this block is from the starter code)
if (!isset($_GET['keyword'])) {
  $keyword = '';
} else {
  $keyword = trim($_GET['keyword']);
}

if (!isset($_GET['cat'])) {
  $category = 'all';
} else {
  $category = $_GET['cat'];
}

if (!isset($_GET['order_by'])) {
  $ordering = 'pricelow';
} else {
  $ordering = $_GET['order_by'];
}

if (!isset($_GET['page'])) {
  $curr_page = 1;
} else {
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

// Build WHERE clause for active auctions only
$where  = " WHERE a.status = 'active' ";
$params = [];
$types  = '';

// Keyword filter (search in title and description)
if ($keyword !== '') {
  $where .= " AND (i.title LIKE ? OR i.description LIKE ?) ";
  $like = '%' . $keyword . '%';
  $params[] = $like;
  $params[] = $like;
  $types   .= 'ss';
}

// Category filter (if not "all")
if ($category !== 'all' && $category !== '') {
  $where .= " AND i.category_id = ? ";
  $params[] = (int)$category;
  $types   .= 'i';
}

// Ordering for results
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

// Pagination settings
$results_per_page = 10;

// Count total results for pagination
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

// Query actual listings for the current page
$list_sql = "
  SELECT
    a.auction_id,
    a.item_id,
    i.title,
    i.image_path,
    i.description,
    COALESCE(MAX(b.bid_amount), a.start_price) AS current_price,
    COUNT(b.bid_id) AS num_bids,
    a.end_date,
    (SELECT u.name
     FROM bids b2
     JOIN users u ON b2.buyer_id = u.user_id
     WHERE b2.auction_id = a.auction_id
     ORDER BY b2.bid_amount DESC, b2.bid_time ASC
     LIMIT 1) AS current_winner
  FROM auctions a
  JOIN items i ON a.item_id = i.item_id
  LEFT JOIN bids b ON a.auction_id = b.auction_id
  $where
  GROUP BY a.auction_id
  $order_by_sql
  LIMIT ? OFFSET ?
";

// Append LIMIT / OFFSET params
$list_params   = $params;
$list_types    = $types . 'ii';
$list_params[] = $results_per_page;
$list_params[] = $offset;

$result_list = db_query($list_sql, $list_types, $list_params);
?>

<div class="container mt-5">

<ul class="list-group">

<?php
// If no results, show message
if ($num_results == 0) {
  echo '<li class="list-group-item">No results found.</li>';
} else {
  $now = new DateTime();

  // Loop through each auction and render HTML, including thumbnail image
  while ($row = $result_list->fetch_assoc()) {
    $item_id       = (int)$row['item_id'];
    $title         = $row['title'];
    $description   = $row['description'];
    $current_price = (float)$row['current_price'];
    $num_bids      = (int)$row['num_bids'];
    $end_date      = new DateTime($row['end_date']);
    $image_path    = $row['image_path'];
    $current_winner = $row['current_winner'] ?? null;

    // Compute time remaining text
    $time_remaining = '';
    if ($now < $end_date) {
      $diff = date_diff($now, $end_date);
      $time_remaining = display_time_remaining($diff) . ' remaining';
    } else {
      $time_remaining = 'Ended';
    }

    // Truncate description for list view
    $short_desc = $description;
    if (strlen($short_desc) > 140) {
      $short_desc = substr($short_desc, 0, 137) . '...';
    }

    // Start list item
    ?>
    <li class="list-group-item"
        style="background-color: #111; border: 1px solid #333; margin-bottom: 10px; border-radius: 4px;">
      <div class="row align-items-center">

        <div class="col-md-2">
          <?php
          // If an image exists, show it; otherwise show the Stark placeholder box
          if (!empty($image_path) && file_exists($image_path)) {
            echo '<img src="' . htmlspecialchars($image_path) . '" alt="Item image" class="img-fluid"
                     style="width: 140px; height: 140px; object-fit: cover; border-radius: 4px;">';
          } else {
            // Fallback div uses the original CSS "IMG PENDING" background
            echo '<div class="img-placeholder-sm" style="width: 140px; height: 140px;"></div>';
          }
          ?>
        </div>

        <div class="col-md-7">
          <h5 class="mb-1">
            <a href="listing.php?item_id=<?php echo $item_id; ?>"
               class="text-light"
               style="font-family: \'Oswald\', sans-serif; letter-spacing: 0.5px;">
              <?php echo htmlspecialchars($title); ?>
            </a>
          </h5>
          <p class="mb-1 text-muted" style="font-size: 0.9rem;">
            <?php echo htmlspecialchars($short_desc); ?>
          </p>
          <small class="text-warning">
            <?php echo $end_date->format('j M H:i'); ?><?php echo $time_remaining ? ' · ' . $time_remaining : ''; ?>
          </small>
        </div>

        <div class="col-md-3 text-right">
          <div style="font-size: 1.5rem; font-weight: bold; color: var(--color-accent);">
            £<?php echo number_format($current_price, 2); ?>
          </div>
          <div class="text-muted small mb-2">
            <?php echo $num_bids; ?> bid(s)
          </div>
          <?php if ($current_winner): ?>
          <div class="text-info small mb-2">
            <i class="fas fa-trophy"></i> Leading: <?php echo htmlspecialchars($current_winner); ?>
          </div>
          <?php endif; ?>
          <a href="listing.php?item_id=<?php echo $item_id; ?>" class="btn btn-outline-warning btn-sm">
            VIEW
          </a>
        </div>

      </div>
    </li>
    <?php
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
  $low_page_boost  = max(2 - ($max_page - $curr_page), 0);
  $low_page        = max(1, $curr_page - 2 - $low_page_boost);
  $high_page       = min($max_page, $curr_page + 2 + $high_page_boost);

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
      echo('<li class="page-item active">');
    } else {
      echo('<li class="page-item">');
    }

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

<?php include_once("footer.php"); ?>
