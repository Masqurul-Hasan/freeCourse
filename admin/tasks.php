<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'Tasks';
$meta_description = 'Admin task management';

$stmt = $pdo->query("
    SELECT *
    FROM tasks
    ORDER BY id DESC
");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card admin-panel-card">
            <div class="section-head">
                <h1>Task Management</h1>
                <p>নতুন task তৈরি করুন এবং সব task manage করুন</p>
            </div>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <div class="form-actions" style="margin-bottom: 20px;">
                <a href="<?= SITE_URL; ?>/admin/create-task.php" class="btn-primary">নতুন Task তৈরি করুন</a>
                <a href="<?= SITE_URL; ?>/admin/task-submissions.php" class="btn-light">Task Submissions</a>
            </div>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">এখনো কোনো task তৈরি করা হয়নি।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Reward</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $index => $task): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td>
                                        <strong><?= e($task['title']); ?></strong><br>
                                        <small><?= e($task['description'] ?: 'N/A'); ?></small>
                                    </td>
                                    <td><?= e($task['task_type']); ?></td>
                                    <td>৳<?= number_format((float)$task['reward_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($task['status'] === 'active'): ?>
                                            <span class="status-badge status-approved">active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-rejected">inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($task['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>