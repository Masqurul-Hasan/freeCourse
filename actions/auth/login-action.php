<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

/* =========================================================
   1. REQUEST METHOD CHECK
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   2. CSRF VALIDATION
   ========================================================= */
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   3. GET INPUT
   ========================================================= */
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

/* =========================================================
   4. BASIC VALIDATION
   ========================================================= */
if ($phone === '' || $password === '') {
    setFlash('error', 'মোবাইল নাম্বার ও পাসওয়ার্ড দিন।');
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   5. FIND USER
   ========================================================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
$stmt->execute([$phone]);
$user = $stmt->fetch();

/* =========================================================
   6. LOGIN CHECK
   ========================================================= */
if (!$user || $password !== $user['password']) {
    setFlash('error', 'মোবাইল নাম্বার বা পাসওয়ার্ড সঠিক নয়।');
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   7. ACCOUNT STATUS CHECK
   ========================================================= */
if ($user['account_status'] !== 'active') {
    setFlash('error', 'এই অ্যাকাউন্টটি সক্রিয় নয়।');
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   8. CREATE SESSION
   ========================================================= */
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_uid'] = $user['user_uid'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_phone'] = $user['phone'];
$_SESSION['kyc_status'] = $user['kyc_status'];
$_SESSION['account_status'] = $user['account_status'];

/* =========================================================
   9. REDIRECT
   ========================================================= */
setFlash('success', 'সফলভাবে লগইন হয়েছে।');
redirect(SITE_URL . '/dashboard.php');