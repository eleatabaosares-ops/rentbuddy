<?php
/**
 * RentBuddy - Sidebar Navigation
 * Location: includes/sidebar.php
 * Requires: current_user() from functions.php
 */
$user = current_user();
$role = $user['role'] ?? 'tenant';
$base = BASE_URL . ($role === 'admin' ? 'admin/' : 'tenant/');
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>assets/img/logo.svg" alt="RentBuddy" class="brand-logo">
        <span class="brand-name">RentBuddy</span>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= $base ?>dashboard.php" class="<?= $current==='dashboard.php'?'active':'' ?>">
            <span class="nav-icon">▦</span> Dashboard
        </a>
        <?php if ($role === 'admin'): ?>
            <a href="<?= $base ?>properties.php" class="<?= $current==='properties.php'?'active':'' ?>">
                <span class="nav-icon">🏢</span> Properties/Units
            </a>
            <a href="<?= $base ?>tenants.php" class="<?= $current==='tenants.php'?'active':'' ?>">
                <span class="nav-icon">👥</span> Tenants
            </a>
            <a href="<?= $base ?>leases.php" class="<?= $current==='leases.php'?'active':'' ?>">
                <span class="nav-icon">📄</span> Lease Tracking
            </a>
            <a href="<?= $base ?>payments.php" class="<?= $current==='payments.php'?'active':'' ?>">
                <span class="nav-icon">💵</span> Rent &amp; Payments
            </a>
            <a href="<?= $base ?>reminders.php" class="<?= $current==='reminders.php'?'active':'' ?>">
                <span class="nav-icon">🔔</span> Rent Reminders
            </a>
            <a href="<?= $base ?>maintenance.php" class="<?= $current==='maintenance.php'?'active':'' ?>">
                <span class="nav-icon">🛠</span> Maintenance
            </a>
            <a href="<?= $base ?>documents.php" class="<?= $current==='documents.php'?'active':'' ?>">
                <span class="nav-icon">📁</span> Documents
            </a>
            <a href="<?= $base ?>reports.php" class="<?= $current==='reports.php'?'active':'' ?>">
                <span class="nav-icon">📊</span> Reports
            </a>
        <?php else: ?>
            <a href="<?= $base ?>profile.php" class="<?= $current==='profile.php'?'active':'' ?>">
                <span class="nav-icon">👤</span> My Profile
            </a>
            <a href="<?= $base ?>lease.php" class="<?= $current==='lease.php'?'active':'' ?>">
                <span class="nav-icon">📄</span> My Lease
            </a>
            <a href="<?= $base ?>payments.php" class="<?= $current==='payments.php'?'active':'' ?>">
                <span class="nav-icon">💵</span> Rent &amp; Payments
            </a>
            <a href="<?= $base ?>maintenance.php" class="<?= $current==='maintenance.php'?'active':'' ?>">
                <span class="nav-icon">🛠</span> Maintenance
            </a>
            <a href="<?= $base ?>documents.php" class="<?= $current==='documents.php'?'active':'' ?>">
                <span class="nav-icon">📁</span> Documents
            </a>
        <?php endif; ?>
        <a href="<?= $base ?>notifications.php" class="<?= $current==='notifications.php'?'active':'' ?>">
            <span class="nav-icon">🔔</span> Notifications
        </a>
        <?php if ($role === 'admin'): ?>
            <a href="<?= $base ?>settings.php" class="<?= $current==='settings.php'?'active':'' ?>">
                <span class="nav-icon">⚙</span> Settings
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>logout.php" class="logout-link">
            <span class="nav-icon">⎋</span> Logout
        </a>
    </nav>
</aside>