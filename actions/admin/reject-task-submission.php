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
        SELECT *
        FROM task_submissions
        WHERE id = ?
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

    $stmt = $pdo->prepare("
        UPDATE task_submissions
        SET status = 'rejected', reviewed_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$submissionId]);

    $userId = (int)$submission['user_id'];

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
        'Task Rejected',
        'দুঃখিত, আপনার task submission reject করা হয়েছে।'
    ]);

    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    if ($adminId > 0) {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $logStmt->execute([$adminId, 'task_submission_rejected', $submissionId]);
    }

    $pdo->commit();

    setFlash('success', 'Task submission rejected.');
    redirect(SITE_URL . '/admin/task-submissions.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'Reject error: ' . $e->getMessage());
    redirect(SITE_URL . '/admin/task-submissions.php');
}