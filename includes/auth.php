<?php
/**
 * RentBuddy - Authentication Guard
 * Location: includes/auth.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function require_login() {
    if (!current_user()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: ' . BASE_URL . 'tenant/dashboard.php');
        exit;
    }
}

function require_tenant() {
    require_login();
    if (!is_tenant()) {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit;
    }
}