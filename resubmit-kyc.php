<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM kyc_submissions
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$kyc = $stmt->fetch();

if (!$kyc) {
    setFlash('error', 'KYC তথ্য পাওয়া যায়নি।');
    redirect(SITE_URL . '/dashboard.php');
}

if ($kyc['status'] !== 'resubmit_required' && $_SESSION['kyc_status'] !== 'resubmit_required') {
    redirect(SITE_URL . '/dashboard.php');
}

$page_title = 'KYC পুনরায় জমা দিন';
$meta_description = 'KYC পুনরায় জমা দিন';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card auth-card auth-card-wide">
            <div class="auth-head">
                <h1>KYC পুনরায় জমা দিন</h1>
                <p>এডমিনের মন্তব্য দেখে সংশোধন করে আবার KYC জমা দিন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if (!empty($kyc['admin_comment'])): ?>
                <div class="alert alert-warning">
                    <strong>এডমিন মন্তব্য:</strong><br>
                    <?= nl2br(e($kyc['admin_comment'])); ?>
                </div>
            <?php endif; ?>

            <form action="<?= SITE_URL; ?>/actions/user/kyc-resubmit-action.php" method="POST" enctype="multipart/form-data" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="kyc_id" value="<?= (int) $kyc['id']; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="nid_number">এনআইডি নাম্বার</label>
                        <input
                            type="text"
                            id="nid_number"
                            name="nid_number"
                            value="<?= e($kyc['nid_number']); ?>"
                            placeholder="আপনার জাতীয় পরিচয়পত্র নম্বর"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">জন্ম তারিখ</label>
                        <input
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            value="<?= e($kyc['date_of_birth']); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bkash_number">বিকাশ নাম্বার</label>
                        <input
                            type="tel"
                            id="bkash_number"
                            name="bkash_number"
                            value="<?= e($kyc['bkash_number']); ?>"
                            placeholder="01XXXXXXXXX"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="nid_front_image">নতুন এনআইডি ফ্রন্ট ছবি</label>
                    <input type="file" id="nid_front_image" name="nid_front_image" accept=".jpg,.jpeg,.png" required>
                    <small>সর্বোচ্চ ৫MB • JPG / JPEG / PNG</small>
                </div>

                <div class="form-group">
                    <label for="nid_back_image">নতুন এনআইডি ব্যাক ছবি</label>
                    <input type="file" id="nid_back_image" name="nid_back_image" accept=".jpg,.jpeg,.png" required>
                    <small>সর্বোচ্চ ৫MB • JPG / JPEG / PNG</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary auth-submit">পুনরায় KYC জমা দিন</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>