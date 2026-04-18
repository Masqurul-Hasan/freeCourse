<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/tasks.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid CSRF token.');
    redirect(SITE_URL . '/admin/create-task.php');
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$rewardAmount = (float)($_POST['reward_amount'] ?? 0);
$taskType = trim($_POST['task_type'] ?? 'custom');
$taskUrl = trim($_POST['task_url'] ?? '');
$status = trim($_POST['status'] ?? 'active');

if ($title === '' || $rewardAmount <= 0) {
    setFlash('error', 'সব required field ঠিকভাবে পূরণ করুন।');
    redirect(SITE_URL . '/admin/create-task.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            title,
            description,
            reward_amount,
            task_type,
            task_url,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $title,
        $description !== '' ? $description : null,
        $rewardAmount,
        $taskType,
        $taskUrl !== '' ? $taskUrl : null,
        $status
    ]);

    $taskId = (int)$pdo->lastInsertId();
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    if ($adminId > 0) {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $logStmt->execute([$adminId, 'task_created', $taskId]);
    }

    $pdo->commit();

    setFlash('success', 'Task created successfully.');
    redirect(SITE_URL . '/admin/tasks.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'Task create error: ' . $e->getMessage());
    redirect(SITE_URL . '/admin/create-task.php');
}