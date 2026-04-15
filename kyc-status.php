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
$kyc = $stmt->fetch();

if (!$kyc) {
    redirect(SITE_URL . '/submit-kyc.php');
}

$page_title = 'KYC স্ট্যাটাস';
$meta_description = 'আপনার KYC স্ট্যাটাস দেখুন';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card" style="padding:24px;">
            <div class="section-head">
                <h1>আপনার KYC স্ট্যাটাস</h1>
                <p>এখানে আপনার যাচাইকরণের বর্তমান অবস্থা দেখানো হচ্ছে</p>
            </div>

            <div style="display:grid; gap:16px;">
                <div>
                    <strong>স্ট্যাটাস:</strong>
                    <?php if ($kyc['status'] === 'pending'): ?>
                        <span class="status-badge status-pending">Pending</span>
                    <?php elseif ($kyc['status'] === 'approved'): ?>
                        <span class="status-badge status-approved">Approved</span>
                    <?php elseif ($kyc['status'] === 'rejected'): ?>
                        <span class="status-badge status-rejected">Rejected</span>
                    <?php elseif ($kyc['status'] === 'resubmit_required'): ?>
                        <span class="status-badge status-pending">Resubmit Required</span>
                    <?php endif; ?>
                </div>

                <div>
                    <strong>এনআইডি নাম্বার:</strong>
                    <div><?= e($kyc['nid_number']); ?></div>
                </div>

                <div>
                    <strong>জন্ম তারিখ:</strong>
                    <div><?= e($kyc['date_of_birth']); ?></div>
                </div>

                <div>
                    <strong>বিকাশ নাম্বার:</strong>
                    <div><?= e($kyc['bkash_number']); ?></div>
                </div>

                <div>
                    <strong>জমা দেওয়ার সময়:</strong>
                    <div><?= e($kyc['submitted_at']); ?></div>
                </div>

                <?php if (!empty($kyc['admin_comment'])): ?>
                    <div class="alert alert-warning">
                        <strong>এডমিন মন্তব্য:</strong><br>
                        <?= nl2br(e($kyc['admin_comment'])); ?>
                    </div>
                <?php endif; ?>

                <?php if ($kyc['status'] === 'resubmit_required'): ?>
                    <div>
                        <a href="<?= SITE_URL; ?>/resubmit-kyc.php" class="btn-primary" style="display:inline-flex;">
                            পুনরায় জমা দিন
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>