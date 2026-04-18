<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        t.*,
        (
            SELECT ts.status
            FROM task_submissions ts
            WHERE ts.task_id = t.id AND ts.user_id = ?
            ORDER BY ts.id DESC
            LIMIT 1
        ) AS submission_status
    FROM tasks t
    WHERE t.status = 'active'
    ORDER BY t.id DESC
");
$stmt->execute([$userId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Tasks';
$meta_description = 'Available earning tasks';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card dashboard-card">
            <div class="section-head">
                <h1>Available Tasks</h1>
                <p>নিচের task complete করে earning করুন</p>
            </div>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">এখন কোনো active task নেই।</div>
            <?php else: ?>
                <div class="stats-grid dashboard-stats-grid">
                    <?php foreach ($tasks as $task): ?>
                        <div class="info-card">
                            <h3><?= e($task['title']); ?></h3>

                            <p style="margin-bottom:10px;">
                                <?= e($task['description'] ?: 'No description'); ?>
                            </p>

                            <p><strong>Reward:</strong> ৳<?= number_format((float)$task['reward_amount'], 2); ?></p>
                            <p><strong>Type:</strong> <?= e(ucfirst($task['task_type'])); ?></p>

                            <?php if (!empty($task['task_url'])): ?>
                                <div class="form-actions" style="margin-top:12px; margin-bottom:12px;">
                                    <a href="<?= e($task['task_url']); ?>" target="_blank" class="btn-light">Open Task Link</a>
                                </div>
                            <?php endif; ?>

                            <?php if ($task['submission_status'] === 'approved'): ?>
                                <div style="margin-top:14px;">
                                    <span class="status-badge status-approved">Approved</span>
                                </div>

                            <?php elseif ($task['submission_status'] === 'pending'): ?>
                                <div style="margin-top:14px;">
                                    <span class="status-badge status-pending">Pending Review</span>
                                </div>

                            <?php else: ?>
                                <form
                                    action="<?= SITE_URL; ?>/actions/user/submit-task-action.php"
                                    method="POST"
                                    enctype="multipart/form-data"
                                    style="margin-top:14px;"
                                >
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">

                                    <div class="form-group">
                                        <label style="display:block; margin-bottom:8px;">Proof Note (optional)</label>
                                        <textarea
                                            name="proof_note"
                                            rows="3"
                                            placeholder="Task complete করার proof / note লিখুন"
                                        ></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label style="display:block; margin-bottom:8px;">Screenshot / Proof Image</label>
                                        <input
                                            type="file"
                                            name="proof_image"
                                            accept=".jpg,.jpeg,.png,.webp"
                                            required
                                        >
                                        <small style="display:block; margin-top:6px; color:#666;">
                                            JPG, JPEG, PNG, WEBP allowed
                                        </small>
                                    </div>

                                    <div class="form-actions" style="margin-top:14px;">
                                        <button type="submit" class="btn-primary">Submit Task</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>