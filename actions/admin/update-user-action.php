<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/users.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট।');
    redirect(SITE_URL . '/admin/users.php');
}

$user_id = (int) ($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$kyc_status = trim($_POST['kyc_status'] ?? '');
$account_status = trim($_POST['account_status'] ?? '');

if ($user_id <= 0 || $name === '' || $phone === '' || $password === '' || $kyc_status === '' || $account_status === '') {
    setFlash('error', 'সব প্রয়োজনীয় তথ্য পূরণ করুন।');
    redirect(SITE_URL . '/admin/edit-user.php?id=' . $user_id);
}

// Duplicate phone check excluding current user
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1");
$stmt->execute([$phone, $user_id]);
if ($stmt->fetch()) {
    setFlash('error', 'এই মোবাইল নাম্বার অন্য একটি ইউজারের সাথে মিলে গেছে।');
    redirect(SITE_URL . '/admin/edit-user.php?id=' . $user_id);
}

// Duplicate email check excluding current user
if ($email !== '') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        setFlash('error', 'এই ইমেইল অন্য একটি ইউজারের সাথে মিলে গেছে।');
        redirect(SITE_URL . '/admin/edit-user.php?id=' . $user_id);
    }
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE users
        SET
            name = ?,
            phone = ?,
            email = ?,
            password = ?,
            kyc_status = ?,
            account_status = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $name,
        $phone,
        $email !== '' ? $email : null,
        $password,
        $kyc_status,
        $account_status,
        $user_id
    ]);

    // Keep latest KYC row in sync with user's KYC status
    $stmt = $pdo->prepare("
        UPDATE kyc_submissions
        SET
            status = ?,
            updated_at = NOW()
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$kyc_status, $user_id]);

    $pdo->commit();

    setFlash('success', 'ইউজার তথ্য সফলভাবে আপডেট হয়েছে।');
    redirect(SITE_URL . '/admin/user-details.php?id=' . $user_id);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'ইউজার তথ্য আপডেট করা যায়নি।');
    redirect(SITE_URL . '/admin/edit-user.php?id=' . $user_id);
}