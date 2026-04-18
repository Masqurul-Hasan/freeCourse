<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/task-submissions.php');
}

$submissionId = (int)($_POST['submission_id'] ?? 0);

if ($submissionId <= 0) {
    setFlash('error', 'Invalid submission id.');
    redirect(SITE_URL . '/admin/task-submissions.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT
            ts.*,
            t.reward_amount
        FROM task_submissions ts
        INNER JOIN tasks t ON t.id = ts.task_id
        WHERE ts.id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$submissionId]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        $pdo->rollBack();
        setFlash('error', 'Submission not found.');
        redirect(SITE_URL . '/admin/task-submissions.php');
    }

    if ($submission['status'] !== 'pending') {
        $pdo->rollBack();
        setFlash('error', 'This submission is already processed.');
        redirect(SITE_URL . '/admin/task-submissions.php');
    }

    $userId = (int)$submission['user_id'];
    $taskId = (int)$submission['task_id'];
    $reward = (float)$submission['reward_amount'];

    $stmt = $pdo->prepare("
        SELECT id, wallet_balance
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
        redirect(SITE_URL . '/admin/task-submissions.php');
    }

    $newBalance = (float)$user['wallet_balance'] + $reward;

    $stmt = $pdo->prepare("
        UPDATE users
        SET wallet_balance = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$newBalance, $userId]);

    $stmt = $pdo->prepare("
        UPDATE task_submissions
        SET status = 'approved', reviewed_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$submissionId]);

    $stmt = $pdo->prepare("
        INSERT INTO user_task_completions (
            task_id,
            user_id,
            reward_amount,
            created_at
        ) VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$taskId, $userId, $reward]);

    $stmt = $pdo->prepare("
        INSERT INTO wallet_transactions (
            user_id,
            amount,
            type,
            source,
            reference_id,
            description,
            created_at
        ) VALUES (?, ?, 'credit', 'admin_adjustment', ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $reward,
        $submissionId,
        'Task reward approved by admin'
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
        'Task Approved',
        'আপনার task submission approved হয়েছে। Reward: ৳' . number_format($reward, 2)
    ]);

    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    if ($adminId > 0) {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $logStmt->execute([$adminId, 'task_submission_approved', $submissionId]);
    }

    $pdo->commit();

    setFlash('success', 'Task approved and reward added.');
    redirect(SITE_URL . '/admin/task-submissions.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'Approve error: ' . $e->getMessage());
    redirect(SITE_URL . '/admin/task-submissions.php');
}