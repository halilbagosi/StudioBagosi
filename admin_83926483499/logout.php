<?php
session_start();

// Clear only admin-related session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

header('Location: login.php');
exit;
?> 