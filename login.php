<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'email'     => $user['email'],
                'role'      => $user['role'],
                'tenant_id' => $user['tenant_id'],
            ];
            
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            // Explicitly use the BASE_URL constant defined in database.php
            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
                exit;
            } else {
                header('Location: ' . BASE_URL . 'tenant/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RentBuddy</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <img src="assets/img/logo.svg" alt="RentBuddy" class="brand-logo">
            <h1>RentBuddy</h1>
            <p>Property/Rental Management System</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
    </div>
</body>
</html>