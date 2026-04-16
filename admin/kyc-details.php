<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$kyc_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($kyc_id <= 0) {
    setFlash('error', 'অবৈধ KYC আইডি।');
    redirect(SITE_URL . '/admin/pending-kyc.php');
}

$stmt = $pdo->prepare("
    SELECT 
        k.*,
        u.id AS user_id,
        u.user_uid,
        u.name,
        u.phone,
        u.email,
        u.kyc_status,
        u.account_status
    FROM kyc_submissions k
    INNER JOIN users u ON k.user_id = u.id
    WHERE k.id = ?
    LIMIT 1
");
$stmt->execute([$kyc_id]);
$kyc = $stmt->fetch();

if (!$kyc) {
    setFlash('error', 'KYC তথ্য পাওয়া যায়নি।');
    redirect(SITE_URL . '/admin/pending-kyc.php');
}

$page_title = 'KYC রিভিউ';
$meta_description = 'KYC রিভিউ পেজ';

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card" style="padding:24px;">
            <div class="section-head">
                <h1>KYC রিভিউ</h1>
                <p>ইউজারের তথ্য যাচাই করুন এবং প্রয়োজনীয় action নিন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <div class="review-grid">
                <div class="card info-card">
                    <h3>ইউজার তথ্য</h3>
                    <div class="review-list">
                        <div><strong>নাম:</strong> <?= e($kyc['name']); ?></div>
                        <div><strong>ইউজার UID:</strong> <?= e($kyc['user_uid']); ?></div>
                        <div><strong>মোবাইল:</strong> <?= e($kyc['phone']); ?></div>
                        <div><strong>ইমেইল:</strong> <?= e($kyc['email'] ?? 'N/A'); ?></div>
                        <div><strong>NID নাম্বার:</strong> <?= e($kyc['nid_number']); ?></div>
                        <div><strong>জন্ম তারিখ:</strong> <?= e($kyc['date_of_birth']); ?></div>
                        <div><strong>বিকাশ নাম্বার:</strong> <?= e($kyc['bkash_number']); ?></div>
                        <div><strong>জমা দেওয়ার সময়:</strong> <?= e($kyc['submitted_at']); ?></div>
                        <div>
                            <strong>স্ট্যাটাস:</strong>
                            <span class="status-badge status-pending"><?= e($kyc['status']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="card info-card">
                    <h3>NID ফ্রন্ট</h3>
                    <div class="kyc-image-wrap">
                        <img src="<?= SITE_URL . '/' . e($kyc['nid_front_image']); ?>" alt="NID Front" class="kyc-preview-image">
                    </div>
                    <div class="form-actions">
                        <a href="<?= SITE_URL . '/' . e($kyc['nid_front_image']); ?>" target="_blank" class="btn-light">Full View</a>
                        <a href="<?= SITE_URL . '/' . e($kyc['nid_front_image']); ?>" download class="btn-primary">Download</a>
                    </div>
                </div>

                <div class="card info-card">
                    <h3>NID ব্যাক</h3>
                    <div class="kyc-image-wrap">
                        <img src="<?= SITE_URL . '/' . e($kyc['nid_back_image']); ?>" alt="NID Back" class="kyc-preview-image">
                    </div>
                    <div class="form-actions">
                        <a href="<?= SITE_URL . '/' . e($kyc['nid_back_image']); ?>" target="_blank" class="btn-light">Full View</a>
                        <a href="<?= SITE_URL . '/' . e($kyc['nid_back_image']); ?>" download class="btn-primary">Download</a>
                    </div>
                </div>
            </div>

            <div class="card info-card review-actions-card" style="margin-top:24px;">
                <h3>রিভিউ অ্যাকশন</h3>

                <form action="<?= SITE_URL; ?>/actions/admin/approve-kyc-action.php" method="POST" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                    <input type="hidden" name="kyc_id" value="<?= (int) $kyc['id']; ?>">
                    <input type="hidden" name="user_id" value="<?= (int) $kyc['user_id']; ?>">
                    <button type="submit" class="btn-success">Approve</button>
                </form>

                <form action="<?= SITE_URL; ?>/actions/admin/reject-kyc-action.php" method="POST" class="review-form-block">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                    <input type="hidden" name="kyc_id" value="<?= (int) $kyc['id']; ?>">
                    <input type="hidden" name="user_id" value="<?= (int) $kyc['user_id']; ?>">

                    <div class="form-group">
                        <label for="reject_comment">Reject Comment</label>
                        <textarea name="admin_comment" id="reject_comment" placeholder="কেন reject করা হচ্ছে তা লিখুন" required></textarea>
                    </div>

                    <button type="submit" class="btn-danger">Reject</button>
                </form>

                <form action="<?= SITE_URL; ?>/actions/admin/resubmit-kyc-action.php" method="POST" class="review-form-block">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                    <input type="hidden" name="kyc_id" value="<?= (int) $kyc['id']; ?>">
                    <input type="hidden" name="user_id" value="<?= (int) $kyc['user_id']; ?>">

                    <div class="form-group">
                        <label for="resubmit_comment">Resubmit Comment</label>
                        <textarea name="admin_comment" id="resubmit_comment" placeholder="যেমন: ছবিটি ঝাপসা, অনুগ্রহ করে পরিষ্কার ছবি দিন" required></textarea>
                    </div>

                    <button type="submit" class="btn-light">Request Resubmission</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>
