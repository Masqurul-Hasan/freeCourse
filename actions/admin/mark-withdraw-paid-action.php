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

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid CSRF token.');
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

    $stmt = $pdo->prepare("
        SELECT id, name, wallet_balance
        FROM users
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $pdo->rollBack();
        setFlash('error', 'User not found.');
        redirect(SITE_URL . '/admin/withdraw-requests.php');
    }

    $amount = (float)$withdraw['amount'];
    $currentBalance = (float)$user['wallet_balance'];

    if ($amount <= 0) {
        $pdo->rollBack();
        setFlash('error', 'Invalid withdraw amount.');
        redirect(SITE_URL . '/admin/withdraw-requests.php');
    }

    if ($currentBalance < $amount) {
        $pdo->rollBack();
        setFlash('error', 'User wallet balance is insufficient.');
        redirect(SITE_URL . '/admin/withdraw-requests.php');
    }

    $newBalance = $currentBalance - $amount;

    $stmt = $pdo->prepare("
        UPDATE users
        SET wallet_balance = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$newBalance, $userId]);

    $adminNote = 'Withdraw request paid successfully by admin.';

    $stmt = $pdo->prepare("
        UPDATE withdraw_requests
        SET
            status = 'paid',
            admin_note = ?,
            processed_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$adminNote, $withdrawId]);

    $stmt = $pdo->prepare("
        INSERT INTO wallet_transactions (
            user_id,
            amount,
            type,
            source,
            reference_id,
            description,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $amount,
        'debit',
        'withdraw',
        $withdrawId,
        'Withdraw request approved and marked as paid by admin'
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
        $userId,
        'Withdraw Approved',
        'অভিনন্দন! আপনার withdraw request approved হয়েছে এবং payment processed করা হয়েছে। Amount: ৳' . number_format($amount, 2)
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
            'withdraw_paid',
            $withdrawId
        ]);
    }

    $pdo->commit();

    setFlash('success', 'Withdraw request marked as paid successfully.');
    redirect(SITE_URL . '/admin/withdraw-requests.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'Withdraw error: ' . $e->getMessage());
    redirect(SITE_URL . '/admin/withdraw-requests.php');
}