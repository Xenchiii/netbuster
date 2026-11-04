<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
csrf_check();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('services.php'); }
$pid = (int)($_POST['plan_id'] ?? 0);
$p = row("SELECT plan_id, plan_name FROM tbl_plans WHERE plan_id=?", 'i', [$pid]);
if (!$p) { flash('error', 'Service not found.'); redirect('services.php'); }
q("INSERT IGNORE INTO tbl_notify (user_id, plan_id) VALUES (?,?)", 'ii', [uid(), $pid]);
log_tx('notify', 'Requested availability alert for ' . $p['plan_name'], $pid);
flash('success', "We'll alert you when {$p['plan_name']} becomes available.");
redirect('plan.php?id=' . $pid);
