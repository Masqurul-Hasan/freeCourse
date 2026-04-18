<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'Create Task';
$meta_description = 'Create new earning task';

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card admin-panel-card">
            <div class="section-head">
                <h1>Create Task</h1>
                <p>নতুন earning task তৈরি করুন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <form action="<?= SITE_URL; ?>/actions/admin/create-task-action.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                <div class="form-group">
                    <label>Task Title</label>
                    <input type="text" name="title" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Reward Amount</label>
                        <input type="number" step="0.01" min="0.01" name="reward_amount" required>
                    </div>

                    <div class="form-group">
                        <label>Task Type</label>
                        <select name="task_type" required>
                            <option value="custom">Custom</option>
                            <option value="video">Video</option>
                            <option value="click">Click</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Task URL (optional)</label>
                    <input type="text" name="task_url">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Task তৈরি করুন</button>
                    <a href="<?= SITE_URL; ?>/admin/tasks.php" class="btn-light">Back</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>