<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}


function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}


function check_admin() {
    check_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: index.php");
        exit;
    }
}
?>
