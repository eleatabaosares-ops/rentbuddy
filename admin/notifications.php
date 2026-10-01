<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'Notifications';

if (isset($_GET['read_all'])) {
    $pdo->exec("UPDATE notifications SET is_read=1");
    header('Location: notifications.php'); exit;
}

$list = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Notifications</h3>
        <a href="?read_all=1" class="btn btn-outline btn-sm">Mark All Read</a>
    </div>
    <ul class="notif-list">
        <?php if (empty($list)): ?>
            <li class="notif-item">No notifications.</li>
        <?php else: foreach ($list as $n): ?>
            <li class="notif-item <?= $n['is_read'] ? '' : 'unread' ?> <?= e($n['type']) ?>">
                <div class="notif-icon">🔔</div>
                <div>
                    <div class="notif-title"><?= e($n['title']) ?></div>
                    <div class="notif-msg"><?= e($n['message']) ?></div>
                    <div class="notif-time"><?= date('M d, Y H:i', strtotime($n['created_at'])) ?></div>
                </div>
            </li>
        <?php endforeach; endif; ?>
    </ul>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>