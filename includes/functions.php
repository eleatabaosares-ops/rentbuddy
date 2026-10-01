<?php
/**
 * RentBuddy - Shared Helper Functions
 * Location: includes/functions.php
 */

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_admin() {
    return (current_user()['role'] ?? '') === 'admin';
}

function is_tenant() {
    return (current_user()['role'] ?? '') === 'tenant';
}

function generate_reference($prefix = 'RB') {
    return $prefix . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function lease_status($end_date) {
    $today = new DateTime();
    $end = new DateTime($end_date);
    $diff = $today->diff($end)->days;
    if ($end < $today) return 'expired';
    if ($diff <= 30) return 'expiring';
    return 'active';
}

function status_badge($status) {
    $map = [
        'paid'         => 'badge-green',
        'unpaid'       => 'badge-gray',
        'pending'      => 'badge-yellow',
        'overdue'      => 'badge-red',
        'active'       => 'badge-green',
        'expiring'     => 'badge-yellow',
        'expired'      => 'badge-red',
        'terminated'   => 'badge-gray',
        'submitted'    => 'badge-blue',
        'under_review' => 'badge-yellow',
        'in_progress'  => 'badge-blue',
        'completed'    => 'badge-green',
        'cancelled'    => 'badge-gray',
        'available'    => 'badge-green',
        'occupied'     => 'badge-blue',
        'maintenance'  => 'badge-yellow',
        'low'          => 'badge-gray',
        'medium'       => 'badge-yellow',
        'high'         => 'badge-red',
        'urgent'       => 'badge-red',
        'upcoming'     => 'badge-green',
        'due'          => 'badge-yellow',
        'contract'     => 'badge-blue',
        'lease'        => 'badge-blue',
        'receipt'      => 'badge-green',
        'other'        => 'badge-gray',
        'info'         => 'badge-blue',
        'success'      => 'badge-green',
    ];
    $cls = $map[$status] ?? 'badge-gray';
    return '<span class="badge ' . $cls . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

function money($amount) {
    return '₱' . number_format((float)$amount, 2);
}

function set_flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf($token) {
    return hash_equals($_SESSION['csrf'] ?? '', $token ?? '');
}

function add_notification($pdo, $title, $message, $type = 'info', $user_id = null, $tenant_id = null) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, tenant_id, title, message, type) VALUES (?,?,?,?,?)");
    $stmt->execute([$user_id, $tenant_id, $title, $message, $type]);
}