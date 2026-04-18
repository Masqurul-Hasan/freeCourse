<?php
require_once __DIR__ . '/../../includes/config.php';
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

if ($user_id <= 0) {
    setFlash('error', 'অবৈধ তথ্য।');
    redirect(SITE_URL . '/admin/pending-kyc.php');
}

try {
    $pdo->beginTransaction();

    if ($kyc_id > 0) {
        $stmt = $pdo->prepare("
            UPDATE kyc_submissions
            SET status = 'approved',
                admin_comment = NULL,
                reviewed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$kyc_id]);
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET kyc_status = 'approved'
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, is_read, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([
        $user_id,
        'KYC অনুমোদিত হয়েছে',
        'অভিনন্দন! আপনার অ্যাকাউন্ট অনুমোদিত হয়েছে। এখন আপনি ড্যাশবোর্ড ব্যবহার করতে পারবেন।'
    ]);

    $pdo->commit();

    setFlash('success', 'KYC সফলভাবে অনুমোদন করা হয়েছে।');
    redirect(SITE_URL . '/admin/pending-kyc.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'KYC approve করা যায়নি।');
    redirect(SITE_URL . '/admin/kyc-details.php?user_id=' . $user_id);
}