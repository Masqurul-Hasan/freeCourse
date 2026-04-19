<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/referral-payouts.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট।');
    redirect(SITE_URL . '/admin/referral-payouts.php');
}

$payoutId = (int)($_POST['payout_id'] ?? 0);

if ($payoutId <= 0) {
    setFlash('error', 'Invalid payout id.');
    redirect(SITE_URL . '/admin/referral-payouts.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM referral_payouts
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$payoutId]);
    $payout = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payout) {
        $pdo->rollBack();
        setFlash('error', 'Referral payout not found.');
        redirect(SITE_URL . '/admin/referral-payouts.php');
    }

    if ($payout['status'] === 'pending') {
        $pdo->rollBack();
        setFlash('error', 'This payout is already pending.');
        redirect(SITE_URL . '/admin/referral-payouts.php');
    }

    $stmt = $pdo->prepare("
        UPDATE referral_payouts
        SET status = 'pending',
            paid_at = NULL
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$payoutId]);

    $referrerUserId = (int)$payout['referrer_user_id'];
    $amount = (float)$payout['amount'];

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
        $referrerUserId,
        'Referral Payout Updated',
        'আপনার referral payout ৳' . number_format($amount, 2) . ' আবার pending করা হয়েছে।'
    ]);

    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    if ($adminId > 0) {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (
                admin_id,
                action,
                target_id,
                created_at
            ) VALUES (?, ?, ?, NOW())
        ");
        $logStmt->execute([
            $adminId,
            'referral_payout_unpaid',
            $payoutId
        ]);
    }

    $pdo->commit();

    setFlash('success', 'Referral payout marked as unpaid successfully.');
    redirect(SITE_URL . '/admin/referral-payouts.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'Referral unpaid error: ' . $e->getMessage());
    redirect(SITE_URL . '/admin/referral-payouts.php');
}