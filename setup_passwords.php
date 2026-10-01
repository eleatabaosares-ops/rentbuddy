<?php
require_once __DIR__ . '/config/database.php';

$adminHash  = password_hash('admin123', PASSWORD_DEFAULT);
$tenantHash = password_hash('tenant123', PASSWORD_DEFAULT);

$pdo->prepare("UPDATE users SET password_hash=? WHERE username='admin'")->execute([$adminHash]);
$pdo->prepare("UPDATE users SET password_hash=? WHERE role='tenant'")->execute([$tenantHash]);

echo "Passwords set successfully.\n";
echo "Admin: admin / admin123\n";
echo "Tenant: john|maria|david|sarah / tenant123\n"; 