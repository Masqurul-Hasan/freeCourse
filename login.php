<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$page_title = 'লগইন';
$meta_description = 'আপনার অ্যাকাউন্টে লগইন করুন';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card auth-card">
            <div class="auth-head">
                <h1>অ্যাকাউন্টে লগইন করুন</h1>
                <p>আপনার মোবাইল নাম্বার ও পাসওয়ার্ড দিন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <form action="<?= SITE_URL; ?>/actions/auth/login-action.php" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                <div class="form-group">
                    <label for="phone">মোবাইল নাম্বার</label>
                    <input type="text" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="password">পাসওয়ার্ড</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn-primary auth-submit">লগইন করুন</button>
            </form>

            <div class="auth-foot">
                <p>অ্যাকাউন্ট নেই? <a href="<?= SITE_URL; ?>/register.php">রেজিস্টার করুন</a></p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>