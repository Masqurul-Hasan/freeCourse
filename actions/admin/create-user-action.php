<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/create-user.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট।');
    redirect(SITE_URL . '/admin/create-user.php');
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$wallet_balance = trim($_POST['wallet_balance'] ?? '0');
$referral_code = trim($_POST['referral_code'] ?? '');
$referred_by = trim($_POST['referred_by'] ?? '');
$kyc_status = trim($_POST['kyc_status'] ?? '');
$account_status = trim($_POST['account_status'] ?? '');

if ($name === '' || $phone === '' || $password === '' || $kyc_status === '' || $account_status === '') {
    setFlash('error', 'সব প্রয়োজনীয় তথ্য পূরণ করুন।');
    redirect(SITE_URL . '/admin/create-user.php');
}

// Phone duplicate check
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    setFlash('error', 'এই মোবাইল নাম্বার ইতোমধ্যে ব্যবহৃত হয়েছে।');
    redirect(SITE_URL . '/admin/create-user.php');
}

// Email duplicate check
if ($email !== '') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        setFlash('error', 'এই ইমেইল ইতোমধ্যে ব্যবহৃত হয়েছে।');
        redirect(SITE_URL . '/admin/create-user.php');
    }
}

// User UID generate
$user_uid = generateUserUID();
do {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE user_uid = ? LIMIT 1");
    $stmt->execute([$user_uid]);
    $existsUid = $stmt->fetch();
    if ($existsUid) {
        $user_uid = generateUserUID();
    }
} while ($existsUid);

// Referral code generate if empty
if ($referral_code === '') {
    $referral_code = generateReferralCode(8);
}

do {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
    $stmt->execute([$referral_code]);
    $existsRef = $stmt->fetch();
    if ($existsRef) {
        $referral_code = generateReferralCode(8);
    }
} while ($existsRef);

// Referred by validation
if ($referred_by !== '') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
    $stmt->execute([$referred_by]);
    if (!$stmt->fetch()) {
        setFlash('error', 'Referred By referral code সঠিক নয়।');
        redirect(SITE_URL . '/admin/create-user.php');
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (
            user_uid,
            name,
            phone,
            email,
            password,
            referral_code,
            referred_by,
            wallet_balance,
            kyc_status,
            account_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_uid,
        $name,
        $phone,
        $email !== '' ? $email : null,
        $password,
        $referral_code,
        $referred_by !== '' ? $referred_by : null,
        (float) $wallet_balance,
        $kyc_status,
        $account_status
    ]);

    $new_user_id = (int) $pdo->lastInsertId();

    // Optional notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, is_read, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([
        $new_user_id,
        'নতুন অ্যাকাউন্ট তৈরি হয়েছে',
        'এডমিন আপনার জন্য একটি নতুন অ্যাকাউন্ট তৈরি করেছেন।'
    ]);

    setFlash('success', 'নতুন ইউজার সফলভাবে তৈরি হয়েছে।');
    redirect(SITE_URL . '/admin/users.php');

} catch (Exception $e) {
    setFlash('error', 'ইউজার তৈরি করা যায়নি।');
    redirect(SITE_URL . '/admin/create-user.php');
}