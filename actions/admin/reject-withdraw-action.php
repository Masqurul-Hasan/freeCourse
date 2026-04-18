<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(SITE_URL . '/admin/withdraw-requests.php');
}

$withdrawId = (int)($_POST['withdraw_id'] ?? 0);
$userId     = (int)($_POST['user_id'] ?? 0);

if ($withdrawId <= 0 || $userId <= 0) {
    setFlash('error', 'Invalid withdraw request data.');
    redirect(SITE_URL . '/admin/withdraw-requests.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM withdraw_requests
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$withdrawId]);
    $withdraw = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$withdraw) {
        $pdo->rollBack();
        setFlash('error', 'Withdraw request not found.');
        redirect(SITE_URL . '/admin/withdraw-requests.php');
    }

    if ((int)$withdraw['user_id'] !== $userId) {
        $pdo->rollBack();
        setFlash('error', 'Withdraw request user mismatch.');
        redirect(SITE_URL . '/admin/withdraw-requests.php');
    }

    if (($withdraw['status'] ?? '') !== 'pending') {
        $pdo->rollBack();
        setFlash('error', 'This withdraw request is already processed.');
        redirect(SITE_URL . '/admin/withdraw-requests.php');
    }

    $adminNote = 'Withdraw request rejected by admin.';

    $stmt = $pdo->prepare("
        UPDATE withdraw_requests
        SET
            status = 'rejected',
            admin_note = ?,
            processed_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$adminNote, $withdrawId]);

    $amount = (float)$withdraw['amount'];

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
        $userId,
        'Withdraw Rejected',
        'দুঃখিত, আপনার withdraw request reject করা হয়েছে। Amount: ৳' . number_format($amount, 2)
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
            'withdraw_rejected',
            $withdrawId
        ]);
    }

    $pdo->commit();

    setFlash('success', 'Withdraw request rejected successfully.');
    redirect(SITE_URL . '/admin/withdraw-requests.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'Withdraw reject error: ' . $e->getMessage());
    redirect(SITE_URL . '/admin/withdraw-requests.php');
}