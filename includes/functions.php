<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url($p = '') { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect($p) { header('Location: ' . url($p)); exit; }
function money($n) { return '₱' . number_format((float)$n, 2); }

function q($sql, $types = '', $params = []) {
    global $conn;
    $st = $conn->prepare($sql);
    if ($types !== '') { $st->bind_param($types, ...$params); }
    $st->execute();
    return $st;
}
function rows($sql, $types = '', $params = []) {
    return q($sql, $types, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
}
function row($sql, $types = '', $params = []) {
    $r = rows($sql, $types, $params);
    return $r[0] ?? null;
}

function flash($type = null, $msg = null) {
    if ($type !== null) { $_SESSION['flash'][] = ['t' => $type, 'm' => $msg]; return null; }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function is_logged_in() { return !empty($_SESSION['user']); }
function is_admin() { return is_logged_in() && $_SESSION['user']['role'] === 'admin'; }
function uid() { return $_SESSION['user']['user_id'] ?? null; }
function require_login() {
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        header('Location: ' . url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'])));
        exit;
    }
}
function require_admin() {
    require_login();
    if (!is_admin()) { http_response_code(403); die('Access denied. Admins only.'); }
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(400);
            die('Invalid form token. Go back, refresh the page and try again.');
        }
    }
}

function plan_available($p) { return $p['status'] === 'Available' && (int)$p['slots'] > 0; }
function cart_count() { return array_sum($_SESSION['cart'] ?? []); }
function log_tx($action, $remarks = '', $record_id = null) {
    q("INSERT INTO tbl_transactions (record_id, user_id, action_type, remarks) VALUES (?,?,?,?)",
      'iiss', [$record_id, uid(), $action, mb_substr($remarks, 0, 255)]);
}
function status_class($s) {
    return ['Pending' => 'warn', 'Technician Assigned' => 'info', 'Activated' => 'ok', 'Cancelled' => 'bad',
            'Paid' => 'ok', 'Available' => 'ok', 'Unavailable' => 'bad'][$s] ?? 'info';
}
