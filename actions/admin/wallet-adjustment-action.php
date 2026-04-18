<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(SITE_URL . '/admin/users.php');
}

$userId = (int)($_POST['user_id'] ?? 0);
$adjustmentType = trim($_POST['adjustment_type'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($userId <= 0) {
    setFlash('error', 'Invalid user ID.');
    redirect(SITE_URL . '/admin/users.php');
}

if (!in_array($adjustmentType, ['credit', 'debit'], true)) {
    setFlash('error', 'Invalid adjustment type.');
    redirect(SITE_URL . '/admin/user-details.php?id=' . $userId);
}

if ($amount <= 0) {
    setFlash('error', 'Amount must be greater than zero.');
    redirect(SITE_URL . '/admin/user-details.php?id=' . $userId);
}

if ($reason === '') {
    setFlash('error', 'Reason is required.');
    redirect(SITE_URL . '/admin/user-details.php?id=' . $userId);
}

try {
    $pdo->beginTransaction();

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
        redirect(SITE_URL . '/admin/users.php');
    }

    $currentBalance = (float)$user['wallet_balance'];

    if ($adjustmentType === 'credit') {
        $newBalance = $currentBalance + $amount;
    } else {
        if ($currentBalance < $amount) {
            $pdo->rollBack();
            setFlash('error', 'Insufficient wallet balance for deduction.');
            redirect(SITE_URL . '/admin/user-details.php?id=' . $userId);
        }

        $newBalance = $currentBalance - $amount;
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET wallet_balance = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$newBalance, $userId]);

    $description = 'Admin wallet adjustment (' . $adjustmentType . '): ' . $reason;

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
        $adjustmentType,
        'admin_adjustment',
        0,
        $description
    ]);

    $notificationTitle = $adjustmentType === 'credit'
        ? 'Wallet Balance Added'
        : 'Wallet Balance Deducted';

    $notificationMessage = $adjustmentType === 'credit'
        ? 'Admin আপনার wallet-এ ৳' . number_format($amount, 2) . ' যোগ করেছেন। Reason: ' . $reason
        : 'Admin আপনার wallet থেকে ৳' . number_format($amount, 2) . ' কেটে নিয়েছেন। Reason: ' . $reason;

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
        $notificationTitle,
        $notificationMessage
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
            'wallet_adjustment',
            $userId
        ]);
    }

    $pdo->commit();

    setFlash('success', 'Wallet balance adjusted successfully.');
    redirect(SITE_URL . '/admin/user-details.php?id=' . $userId);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'Wallet adjust error: ' . $e->getMessage());
    redirect(SITE_URL . '/admin/user-details.php?id=' . $userId);
}