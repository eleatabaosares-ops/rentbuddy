<?php
/**
 * RentBuddy - Header / Layout Start
 * Location: includes/header.php
 * Requires: auth.php loaded before
 */
require_once __DIR__ . '/auth.php';
$user = current_user();
$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?> - RentBuddy</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="app-layout">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="app-main">
    <header class="topbar">
        <button class="menu-toggle" id="menuToggle">☰</button>
        <h2 class="page-title"><?= e($page_title) ?></h2>
        <div class="topbar-user">
            <span class="user-name"><?= e($user['username']) ?></span>
            <span class="user-role"><?= e(ucfirst($user['role'])) ?></span>
        </div>
    </header>
    <main class="content">
    <?php if ($f = get_flash()): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>