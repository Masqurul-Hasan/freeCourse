<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/admin-auth.php';

/* =====================================================
   Support both:
   - ?id=KYC_ID
   - ?user_id=USER_ID
   ===================================================== */
$kyc_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

$kyc = null;
$user = null;

if ($kyc_id > 0) {
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

    if ($kyc) {
        $user = $kyc;
        $user_id = (int)$kyc['user_id'];
    }
}

if (!$user && $user_id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM kyc_submissions
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $kyc = $stmt->fetch();
    }
}

if (!$user) {
    setFlash('error', 'ইউজার বা KYC তথ্য পাওয়া যায়নি।');
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
                        <div><strong>নাম:</strong> <?= e($user['name']); ?></div>
                        <div><strong>ইউজার UID:</strong> <?= e($user['user_uid']); ?></div>
                        <div><strong>মোবাইল:</strong> <?= e($user['phone']); ?></div>
                        <div><strong>ইমেইল:</strong> <?= e($user['email'] ?? 'N/A'); ?></div>
                        <div><strong>KYC স্ট্যাটাস:</strong> <?= e($user['kyc_status']); ?></div>
                        <div><strong>অ্যাকাউন্ট স্ট্যাটাস:</strong> <?= e($user['account_status']); ?></div>
                    </div>
                </div>

                <?php if ($kyc): ?>
                    <div class="card info-card">
                        <h3>KYC তথ্য</h3>
                        <div class="review-list">
                            <div><strong>NID নাম্বার:</strong> <?= e($kyc['nid_number']); ?></div>
                            <div><strong>জন্ম তারিখ:</strong> <?= e($kyc['date_of_birth']); ?></div>
                            <div><strong>বিকাশ নাম্বার:</strong> <?= e($kyc['bkash_number']); ?></div>
                            <div><strong>KYC row স্ট্যাটাস:</strong> <?= e($kyc['status']); ?></div>
                            <div><strong>এডমিন মন্তব্য:</strong> <?= e($kyc['admin_comment'] ?? 'N/A'); ?></div>
                            <div><strong>জমা দেওয়ার সময়:</strong> <?= e($kyc['submitted_at'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <div class="card info-card">
                        <h3>NID ফ্রন্ট</h3>
                        <?php if (!empty($kyc['nid_front_image'])): ?>
                            <div class="kyc-image-wrap">
                                <img src="<?= SITE_URL . '/' . e($kyc['nid_front_image']); ?>" alt="NID Front" class="kyc-preview-image">
                            </div>
                            <div class="form-actions">
                                <a href="<?= SITE_URL . '/' . e($kyc['nid_front_image']); ?>" target="_blank" class="btn-light">Full View</a>
                                <a href="<?= SITE_URL . '/' . e($kyc['nid_front_image']); ?>" download class="btn-primary">Download</a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">এই ইউজার ফ্রন্ট NID submit করেনি।</div>
                        <?php endif; ?>
                    </div>

                    <div class="card info-card">
                        <h3>NID ব্যাক</h3>
                        <?php if (!empty($kyc['nid_back_image'])): ?>
                            <div class="kyc-image-wrap">
                                <img src="<?= SITE_URL . '/' . e($kyc['nid_back_image']); ?>" alt="NID Back" class="kyc-preview-image">
                            </div>
                            <div class="form-actions">
                                <a href="<?= SITE_URL . '/' . e($kyc['nid_back_image']); ?>" target="_blank" class="btn-light">Full View</a>
                                <a href="<?= SITE_URL . '/' . e($kyc['nid_back_image']); ?>" download class="btn-primary">Download</a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">এই ইউজার ব্যাক NID submit করেনি।</div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card info-card" style="grid-column: 1 / -1;">
                        <h3>KYC তথ্য</h3>
                        <div class="alert alert-warning">
                            এই ইউজার এখনো কোনো KYC / NID submit করেনি।
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card info-card review-actions-card" style="margin-top:24px;">
                <h3>রিভিউ অ্যাকশন</h3>

                <form action="<?= SITE_URL; ?>/actions/admin/approve-kyc-action.php" method="POST" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                    <input type="hidden" name="kyc_id" value="<?= (int)($kyc['id'] ?? 0); ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
                    <button type="submit" class="btn-success">Approve</button>
                </form>

                <form action="<?= SITE_URL; ?>/actions/admin/reject-kyc-action.php" method="POST" class="review-form-block">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                    <input type="hidden" name="kyc_id" value="<?= (int)($kyc['id'] ?? 0); ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">

                    <div class="form-group">
                        <label for="reject_comment">Reject Comment</label>
                        <textarea name="admin_comment" id="reject_comment" placeholder="কেন reject করা হচ্ছে তা লিখুন" required></textarea>
                    </div>

                    <button type="submit" class="btn-danger">Reject</button>
                </form>

                <form action="<?= SITE_URL; ?>/actions/admin/resubmit-kyc-action.php" method="POST" class="review-form-block">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                    <input type="hidden" name="kyc_id" value="<?= (int)($kyc['id'] ?? 0); ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">

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