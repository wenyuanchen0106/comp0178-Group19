<?php
require_once 'utilities.php';
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  
  <link rel="stylesheet" href="css/bootstrap.min.css">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="css/custom.css?v=<?php echo time(); ?>">
  
  <title>Stark Exchange</title>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light mx-2">
  <a class="navbar-brand" href="index.php">
    <i class="fa fa-shield" style="margin-right: 5px;"></i> Stark Exchange
  </a>
  
  <ul class="navbar-nav ml-auto">
    <li class="nav-item">

<?php
// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true) {

  // Prefer to display username if set, otherwise fall back to account_type
  $display_name = $_SESSION['username'] ?? $_SESSION['account_type'] ?? 'Agent';
  
  echo '<div class="d-flex align-items-center">';
  
  // Display "Hello, NAME" (name highlighted)
  echo '<span class="navbar-text mr-3" style="color: var(--color-text-light);">'
        . 'Hello, <strong style="color: var(--color-accent); text-transform: uppercase; letter-spacing: 1px;">' 
        . htmlspecialchars($display_name) 
        . '</strong>'
        . '</span>';
        
  // Logout button
  echo '<a class="nav-link" href="logout.php" style="color: #aaa; transition: 0.3s;">'
        . '<i class="fa fa-sign-out"></i> Logout'
        . '</a>';
        
  echo '</div>';

} else {
  // If not logged in, show Login button
  echo '<button type="button" class="btn nav-link" data-toggle="modal" data-target="#loginModal">'
        . '<i class="fa fa-sign-in"></i> Login'
        . '</button>';
}
?>
    </li>
  </ul>
</nav>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <ul class="navbar-nav align-middle">

<?php
// Browse link: visible to buyer and seller, hidden for admin
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
  echo('
    <li class="nav-item mx-1">
      <a class="nav-link" href="browse.php"><i class="fa fa-search"></i> Browse</a>
    </li>
  ');
}

// Buyer-only links
if (isset($_SESSION['account_type']) && $_SESSION['account_type'] == 'buyer') {

    echo('
        <li class="nav-item mx-1">
            <a class="nav-link" href="mybids.php"><i class="fa fa-gavel"></i> My Bids</a>
        </li>
        <li class="nav-item mx-1">
            <a class="nav-link" href="recommendations.php"><i class="fa fa-bullseye"></i> Recommended</a>
        </li>
        <li class="nav-item mx-1">
            <a class="nav-link" href="mywatchlist.php"><i class="fa fa-star"></i> My Watchlist</a>
        </li>
        <li class="nav-item mx-1">
            <a class="nav-link" href="myreports.php"><i class="fa fa-flag"></i> My Reports</a>
        </li>
        <li class="nav-item mx-1 position-relative">
            <a class="nav-link" href="notifications.php">
                <i class="fa fa-bell"></i> Notifications');

    // Insert red badge for unread notifications
    if (!empty($_SESSION['unread_notifications'])) {
        echo '
                <span class="badge badge-danger position-absolute"
                      style="top: 0; right: 0; transform: translate(50%, -50%); font-size: 10px;">
                    ' . $_SESSION['unread_notifications'] . '
                </span>';
    }

    // Close link tag
    echo '
            </a>
        </li>
    ';
}

// Seller links
if (isset($_SESSION['account_type']) && $_SESSION['account_type'] == 'seller') {
  echo('
    <li class="nav-item mx-1">
      <a class="nav-link" href="mylistings.php"><i class="fa fa-list"></i> My Listings</a>
    </li>
    <li class="nav-item ml-3">
      <a class="nav-link btn border-light" href="create_auction.php">
        <i class="fa fa-rocket"></i> Create Auction
      </a>
    </li>');
}

// Admin links using role_id
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) {
  echo('
    <li class="nav-item mx-1">
      <a class="nav-link" href="admin_reports.php"><i class="fa fa-flag"></i> Reports</a>
    </li>
    <li class="nav-item mx-1">
      <a class="nav-link" href="manage_admins.php"><i class="fa fa-users"></i> Admins</a>
    </li>');
}
?>
<?php
// Additional admin panel link using role_name
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'admin') {
    echo('
        <li class="nav-item mx-1">
            <a class="nav-link" href="admin_dashboard.php">
                <i class="fa fa-shield"></i> Admin Panel
            </a>
        </li>
    ');
}
?>


  </ul>
</nav>

<div class="modal fade" id="loginModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Login</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <form method="POST" action="login_result.php">
          <div class="form-group">
            <label for="loginEmail">Email</label>
            <input type="text" class="form-control" id="loginEmail" name="email" placeholder="Email" required>
          </div>
          <div class="form-group">
            <label for="loginPassword">Password</label>
            <input type="password" class="form-control" id="loginPassword" name="password" placeholder="Password" required>
          </div>
          <button type="submit" class="btn btn-primary form-control">Sign in</button>
        </form>
        <div class="text-center mt-3">
            or <a href="register.php" style="color: var(--color-accent);">create an account</a>
        </div>
      </div>
    </div>
  </div>
</div>

