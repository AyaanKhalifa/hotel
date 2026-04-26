<?php
require_once dirname(__DIR__).'/includes/config.php';
session_unset(); session_destroy(); session_start();
header('Location: '.BASE.'/admin/login.php'); exit;
