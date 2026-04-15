<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/login.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/login.php');
}

$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if ($phone === '' || $password === '') {
    setFlash('error', 'মোবাইল নাম্বার ও পাসওয়ার্ড দিন।');
    redirect(SITE_URL . '/login.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
$stmt->execute([$phone]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    setFlash('error', 'মোবাইল নাম্বার বা পাসওয়ার্ড সঠিক নয়।');
    redirect(SITE_URL . '/login.php');
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_uid'] = $user['user_uid'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_phone'] = $user['phone'];
$_SESSION['kyc_status'] = $user['kyc_status'];
$_SESSION['account_status'] = $user['account_status'];

setFlash('success', 'সফলভাবে লগইন হয়েছে।');
redirect(SITE_URL . '/dashboard.php');