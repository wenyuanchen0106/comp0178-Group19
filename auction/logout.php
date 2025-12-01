<?php

// Logout script to clear session data and redirect to the homepage

session_start();

// Clear key session variables related to authentication and role
unset($_SESSION['user_id']);
unset($_SESSION['role_name']);
unset($_SESSION['logged_in']);
unset($_SESSION['account_type']);

// Invalidate the session cookie on the client
setcookie(session_name(), "", time() - 360);

// Destroy the server-side session
session_destroy();

//test
//var_dump($_SESSION);

// Redirect to index page after logout
header("Location: index.php");
exit();
?>
