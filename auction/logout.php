<?php

session_start();

unset($_SESSION['user_id']);
unset($_SESSION['role_name']);
unset($_SESSION['logged_in']);
unset($_SESSION['account_type']);
setcookie(session_name(), "", time() - 360);
session_destroy();

//test
//var_dump($_SESSION);

// Redirect to index
header("Location: index.php");
exit();
?>