<?php
session_start();

// Only destroy user session variables
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['role']);

// Redirect to homepage
header('Location: ../index.php');
exit; 