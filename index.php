<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$user = current_user();

if ($user) {
    if ($user['role'] === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit;
    } else {
        header('Location: ' . BASE_URL . 'tenant/dashboard.php');
        exit;
    }
} else {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}