<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/withdraw.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/withdraw.php');
}

$user_id = (int) $_SESSION['user_id'];
$amount = (float) ($_POST['amount'] ?? 0);
$payment_method = trim($_POST['payment_method'] ?? '');
$payment_number = trim($_POST['payment_number'] ?? '');

$minimumWithdraw = 1000;
$allowedMethods = ['bkash', 'nagad', 'rocket'];

if ($amount <= 0 || $payment_method === '' || $payment_number === '') {
    setFlash('error', 'সব প্রয়োজনীয় তথ্য পূরণ করুন।');
    redirect(SITE_URL . '/withdraw.php');
}

if (!in_array($payment_method, $allowedMethods, true)) {
    setFlash('error', 'অবৈধ পেমেন্ট মেথড।');
    redirect(SITE_URL . '/withdraw.php');
}

if ($amount < $minimumWithdraw) {
    setFlash('error', 'সর্বনিম্ন withdraw amount হলো ৳1000।');
    redirect(SITE_URL . '/withdraw.php');
}

/* =========================================================
   GET USER DATA
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT id, wallet_balance, kyc_status, account_status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

if (($user['account_status'] ?? '') !== 'active') {
    setFlash('error', 'আপনার অ্যাকাউন্ট active নয়।');
    redirect(SITE_URL . '/withdraw.php');
}

if (($user['kyc_status'] ?? '') !== 'approved') {
    setFlash('error', 'Withdraw করার আগে KYC approved হতে হবে।');
    redirect(SITE_URL . '/withdraw.php');
}

$currentBalance = (float) $user['wallet_balance'];

if ($amount > $currentBalance) {
    setFlash('error', 'আপনার wallet balance এর চেয়ে বেশি withdraw করা যাবে না।');
    redirect(SITE_URL . '/withdraw.php');
}

/* =========================================================
   PREVENT DUPLICATE PENDING REQUESTS
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT id
    FROM withdraw_requests
    WHERE user_id = ? AND status = 'pending'
    LIMIT 1
");
$stmt->execute([$user_id]);
$existingPending = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingPending) {
    setFlash('error', 'আপনার একটি pending withdraw request আগে থেকেই আছে।');
    redirect(SITE_URL . '/withdraw.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO withdraw_requests (
            user_id,
            amount,
            payment_method,
            payment_number,
            status,
            requested_at
        ) VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $user_id,
        $amount,
        $payment_method,
        $payment_number
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            title,
            message,
            is_read,
            created_at
        ) VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([
        $user_id,
        'Withdraw request জমা হয়েছে',
        'আপনার withdraw request সফলভাবে জমা হয়েছে। এডমিন রিভিউ করার পর আপনাকে জানানো হবে।'
    ]);

    $pdo->commit();

    setFlash('success', 'আপনার withdraw request সফলভাবে জমা হয়েছে।');
    redirect(SITE_URL . '/withdraw.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'Withdraw error: ' . $e->getMessage());
    redirect(SITE_URL . '/withdraw.php');
}