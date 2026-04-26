<?php
require_once __DIR__ . '/includes/config.php';
session_unset(); session_destroy(); session_start();
setcookie(session_name(), '', time()-3600, '/');
header('Location: ' . BASE . '/index.php'); exit;
