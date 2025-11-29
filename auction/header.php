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
// 检查是否登录
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true) {

  // ✅ 核心修改：优先显示名字 (username)，如果没有名字则显示角色 (account_type)
  $display_name = $_SESSION['username'] ?? $_SESSION['account_type'] ?? 'Agent';
  
  echo '<div class="d-flex align-items-center">';
  
  // 显示 "Hello, NAME" (名字用金色高亮)
  echo '<span class="navbar-text mr-3" style="color: var(--color-text-light);">'
        . 'Hello, <strong style="color: var(--color-accent); text-transform: uppercase; letter-spacing: 1px;">' 
        . htmlspecialchars($display_name) 
        . '</strong>'
        . '</span>';
        
  // Logout 按钮
  echo '<a class="nav-link" href="logout.php" style="color: #aaa; transition: 0.3s;">'
        . '<i class="fa fa-sign-out"></i> Logout'
        . '</a>';
        
  echo '</div>';

} else {
  // 未登录显示 Login 按钮
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

    <li class="nav-item mx-1">
      <a class="nav-link" href="browse.php"><i class="fa fa-search"></i> Browse</a>
    </li>

<?php
// Buyer 专属链接
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
    </li>');
}

// Seller 专属链接
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