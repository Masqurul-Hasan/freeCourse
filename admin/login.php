<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

if (!empty($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$page_title = 'এডমিন লগইন';
$meta_description = 'এডমিন প্যানেলে লগইন করুন';

include __DIR__ . '/../includes/partials/head.php';
?>

<main class="page-shell auth-page-shell">
    <div class="container">
        <div class="card auth-card">
            <div class="auth-head">
                <h1>এডমিন লগইন</h1>
                <p>এডমিন প্যানেলে প্রবেশ করতে আপনার তথ্য দিন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <form action="<?= SITE_URL; ?>/actions/auth/admin-login-action.php" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                <div class="form-group">
                    <label for="email">এডমিন ইমেইল</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="admin@email.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">পাসওয়ার্ড</label>
                    <div class="password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="আপনার পাসওয়ার্ড লিখুন"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="password-toggle" data-target="password" aria-label="পাসওয়ার্ড দেখুন">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-primary auth-submit">লগইন করুন</button>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁';
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>