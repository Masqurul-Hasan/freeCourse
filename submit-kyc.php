<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

$stmt = $pdo->prepare("SELECT * FROM kyc_submissions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$existingKyc = $stmt->fetch();

if ($existingKyc && in_array($existingKyc['status'], ['pending', 'approved'])) {
    redirect(SITE_URL . '/kyc-status.php');
}

$page_title = 'এনআইডি যাচাইকরণ';
$meta_description = 'এনআইডি তথ্য জমা দিন';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card auth-card">
            <div class="auth-head">
                <h1>এনআইডি যাচাইকরণ জমা দিন</h1>
                <p>আপনার এনআইডি ফ্রন্ট, ব্যাক এবং প্রয়োজনীয় তথ্য দিন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <form action="<?= SITE_URL; ?>/actions/user/kyc-upload-action.php" method="POST" enctype="multipart/form-data" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                <div class="form-group">
                    <label for="nid_number">এনআইডি নাম্বার</label>
                    <input type="text" id="nid_number" name="nid_number" required>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">জন্ম তারিখ</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>

                <div class="form-group">
                    <label for="bkash_number">বিকাশ নাম্বার</label>
                    <input type="text" id="bkash_number" name="bkash_number" required>
                </div>

                <div class="form-group">
                    <label for="nid_front_image">এনআইডি ফ্রন্ট ছবি</label>
                    <input type="file" id="nid_front_image" name="nid_front_image" accept=".jpg,.jpeg,.png" required>
                    <small>পরিষ্কার, সম্পূর্ণ এবং ঝাপসামুক্ত ছবি দিন</small>
                </div>

                <div class="form-group">
                    <label for="nid_back_image">এনআইডি ব্যাক ছবি</label>
                    <input type="file" id="nid_back_image" name="nid_back_image" accept=".jpg,.jpeg,.png" required>
                    <small>পরিষ্কার, সম্পূর্ণ এবং ঝাপসামুক্ত ছবি দিন</small>
                </div>

                <button type="submit" class="btn-primary auth-submit">যাচাইকরণ জমা দিন</button>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>