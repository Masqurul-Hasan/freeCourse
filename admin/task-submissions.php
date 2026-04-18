<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$stmt = $pdo->query("
    SELECT
        ts.*,
        t.title AS task_title,
        t.reward_amount,
        u.name AS user_name,
        u.user_uid,
        u.phone
    FROM task_submissions ts
    INNER JOIN tasks t ON t.id = ts.task_id
    INNER JOIN users u ON u.id = ts.user_id
    ORDER BY ts.id DESC
");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Task Submissions';
$meta_description = 'Task submission review';

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card admin-panel-card">
            <div class="section-head">
                <h1>Task Submissions</h1>
                <p>User submitted task review করুন</p>
            </div>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if (empty($submissions)): ?>
                <div class="empty-state">কোনো task submission নেই।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Task</th>
                                <th>Reward</th>
                                <th>Proof</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td>
                                        <strong><?= e($row['user_name']); ?></strong><br>
                                        <small><?= e($row['user_uid']); ?> | <?= e($row['phone']); ?></small>
                                    </td>
                                    <td><?= e($row['task_title']); ?></td>
                                    <td>৳<?= number_format((float)$row['reward_amount'], 2); ?></td>
                                    <td>
                                        <?php if (!empty($row['proof_note'])): ?>
                                            <div style="margin-bottom:8px;"><?= e($row['proof_note']); ?></div>
                                        <?php endif; ?>

                                        <?php if (!empty($row['proof_image'])): ?>
                                            <a href="<?= SITE_URL . '/' . e($row['proof_image']); ?>" target="_blank" class="btn-light">
                                                View Screenshot
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#888;">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'approved'): ?>
                                            <span class="status-badge status-approved">approved</span>
                                        <?php elseif ($row['status'] === 'rejected'): ?>
                                            <span class="status-badge status-rejected">rejected</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($row['submitted_at']); ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <form action="<?= SITE_URL; ?>/actions/admin/approve-task-submission.php" method="POST" style="display:inline-block;">
                                                <input type="hidden" name="submission_id" value="<?= (int)$row['id']; ?>">
                                                <button type="submit" class="btn-primary">Approve</button>
                                            </form>

                                            <form action="<?= SITE_URL; ?>/actions/admin/reject-task-submission.php" method="POST" style="display:inline-block;">
                                                <input type="hidden" name="submission_id" value="<?= (int)$row['id']; ?>">
                                                <button type="submit" class="btn-light">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#888;">Processed</span>
                                        <?php endif; ?>
                                    </td>
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