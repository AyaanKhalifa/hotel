<?php
require_once __DIR__ . '/includes/config.php';

// Destroy staff session
unset($_SESSION['staff_id']);
unset($_SESSION['staff_name']);
unset($_SESSION['staff_role']);

// Redirect to staff login
header('Location: ' . BASE . '/staff_login.php');
exit;
?>
