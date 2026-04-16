<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($user_id <= 0) {
    setFlash('error', 'অবৈধ ইউজার আইডি।');
    redirect(SITE_URL . '/admin/users.php');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'ইউজার পাওয়া যায়নি।');
    redirect(SITE_URL . '/admin/users.php');
}

try {
    $pdo->beginTransaction();

    // Delete notifications first
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Delete kyc submissions
    $stmt = $pdo->prepare("DELETE FROM kyc_submissions WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Delete wallet transactions if table used
    $stmt = $pdo->prepare("DELETE FROM wallet_transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Delete withdraw requests if table used
    $stmt = $pdo->prepare("DELETE FROM withdraw_requests WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Delete referral rewards where this user is involved
    $stmt = $pdo->prepare("
        DELETE FROM referral_rewards
        WHERE referrer_user_id = ? OR referred_user_id = ?
    ");
    $stmt->execute([$user_id, $user_id]);

    // Finally delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();

    setFlash('success', 'ইউজার সফলভাবে delete করা হয়েছে।');
    redirect(SITE_URL . '/admin/users.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'ইউজার delete করা যায়নি।');
    redirect(SITE_URL . '/admin/users.php');
}