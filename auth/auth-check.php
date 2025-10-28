<?php
// auth/auth-check.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
if (!isLoggedIn()) {
    header('Location: /bms/auth/login.php');
    exit;
}
