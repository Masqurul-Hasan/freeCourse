<?php require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/admin-auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/pending-kyc.php');
}
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট।');
    redirect(SITE_URL . '/admin/pending-kyc.php');
}
$kyc_id = (int) ($_POST['kyc_id'] ?? 0);
$user_id = (int) ($_POST['user_id'] ?? 0);
$admin_comment = trim($_POST['admin_comment'] ?? '');
if ($user_id <= 0 || $admin_comment === '') {
    setFlash('error', 'সব তথ্য সঠিকভাবে দিন।');
    redirect(SITE_URL . '/admin/pending-kyc.php');
}
try {
    $pdo->beginTransaction();
    if ($kyc_id > 0) {
        $stmt = $pdo->prepare(" UPDATE kyc_submissions SET status = 'rejected', admin_comment = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ? ");
        $stmt->execute([$admin_comment, $kyc_id]);
    }
    $stmt = $pdo->prepare(" UPDATE users SET kyc_status = 'rejected' WHERE id = ? ");
    $stmt->execute([$user_id]);
    $stmt = $pdo->prepare(" INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW()) ");
    $stmt->execute([$user_id, 'KYC বাতিল হয়েছে', 'আপনার KYC বাতিল হয়েছে। কারণ: ' . $admin_comment]);
    $pdo->commit();
    setFlash('success', 'KYC reject করা হয়েছে।');
    redirect(SITE_URL . '/admin/pending-kyc.php');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('error', 'KYC reject করা যায়নি।');
    redirect(SITE_URL . '/admin/kyc-details.php?user_id=' . $user_id);
}
