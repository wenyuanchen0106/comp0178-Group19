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
  <link rel="stylesheet" href="css/custom.css">
  <title>[My Auction Site]</title>
</head>

<body>

<!-- Top navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light mx-2">
  <a class="navbar-brand" href="#">Site Name</a>
  <ul class="navbar-nav ml-auto">
    <li class="nav-item">

<?php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true) {

  $role = $_SESSION['account_type'] ?? '';
  $email = $_SESSION['email'] ?? '';

  echo '<div class="d-flex align-items-center">';
  echo '<span class="navbar-text mr-3">'
        . 'Hello, ' . htmlspecialchars($role)
        . (!empty($email) ? ' (' . htmlspecialchars($email) . ')' : '')
        . '</span>';
  echo '<a class="nav-link" href="logout.php">Logout</a>';
  echo '</div>';

} else {
  echo '<button type="button" class="btn nav-link" data-toggle="modal" data-target="#loginModal">Login</button>';
}
?>
    </li>
  </ul>
</nav>

<!-- Dark navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <ul class="navbar-nav align-middle">

    <li class="nav-item mx-1">
      <a class="nav-link" href="browse.php">Browse</a>
    </li>

<?php
// Buyer links
if (isset($_SESSION['account_type']) && $_SESSION['account_type'] == 'buyer') {
  echo('
    <li class="nav-item mx-1">
      <a class="nav-link" href="mybids.php">My Bids</a>
    </li>
    <li class="nav-item mx-1">
      <a class="nav-link" href="recommendations.php">Recommended</a>
    </li>
    <li class="nav-item mx-1">
      <a class="nav-link" href="mywatchlist.php">My Watchlist</a>
    </li>');
}

// Seller links
if (isset($_SESSION['account_type']) && $_SESSION['account_type'] == 'seller') {
  echo('
    <li class="nav-item mx-1">
      <a class="nav-link" href="mylistings.php">My Listings</a>
    </li>
    <li class="nav-item ml-3">
      <a class="nav-link btn border-light" href="create_auction.php">+ Create auction</a>
    </li>');
}
?>

  </ul>
</nav>

<!-- Login modal -->
<div class="modal fade" id="loginModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Login</h4>
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
        <div class="text-center">or <a href="register.php">create an account</a></div>
      </div>
    </div>
  </div>
</div>
