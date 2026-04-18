<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$user_id = (int)($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    setFlash('error', 'অবৈধ ইউজার আইডি।');
    redirect(SITE_URL . '/admin/users.php');
}

$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.name,
        u.phone,
        u.email,
        u.user_uid,

        k.id AS kyc_id,
        k.nid_number,
        k.nid_front_image,
        k.nid_back_image,
        k.submitted_at

    FROM users u

    LEFT JOIN kyc_submissions k
        ON k.id = (
            SELECT ks.id
            FROM kyc_submissions ks
            WHERE ks.user_id = u.id
            ORDER BY ks.id DESC
            LIMIT 1
        )

    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'ইউজার পাওয়া যায়নি।');
    redirect(SITE_URL . '/admin/users.php');
}

$page_title = 'ইউজারের NID';
$meta_description = 'ইউজারের NID ভিউয়ার';

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card admin-panel-card">
            <div class="section-head">
                <h1>ইউজারের NID</h1>
                <p>ইউজারের জমা দেওয়া NID এখানে দেখা যাবে</p>
            </div>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <div class="review-grid">
                <div class="card info-card">
                    <h3>ইউজার তথ্য</h3>
                    <div class="review-list">
                        <div><strong>নাম:</strong> <?= e($user['name']); ?></div>
                        <div><strong>UID:</strong> <?= e($user['user_uid']); ?></div>
                        <div><strong>মোবাইল:</strong> <?= e($user['phone']); ?></div>
                        <div><strong>ইমেইল:</strong> <?= e($user['email'] ?: 'N/A'); ?></div>
                        <div><strong>NID নাম্বার:</strong> <?= e($user['nid_number'] ?: 'N/A'); ?></div>
                        <div><strong>জমা সময়:</strong> <?= e($user['submitted_at'] ?: 'N/A'); ?></div>
                    </div>
                </div>

                <div class="card info-card">
                    <h3>NID ফ্রন্ট</h3>

                    <?php if (!empty($user['nid_front_image'])): ?>
                        <div class="kyc-image-wrap">
                            <img src="<?= SITE_URL . '/' . e($user['nid_front_image']); ?>" alt="NID Front" class="kyc-preview-image">
                        </div>

                        <div class="form-actions">
                            <a href="<?= SITE_URL . '/' . e($user['nid_front_image']); ?>" target="_blank" class="btn-light">
                                View Full
                            </a>

                            <a href="<?= SITE_URL . '/' . e($user['nid_front_image']); ?>" download class="btn-primary">
                                Download
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">এই ইউজার ফ্রন্ট NID submit করেনি।</div>
                    <?php endif; ?>
                </div>

                <div class="card info-card">
                    <h3>NID ব্যাক</h3>

                    <?php if (!empty($user['nid_back_image'])): ?>
                        <div class="kyc-image-wrap">
                            <img src="<?= SITE_URL . '/' . e($user['nid_back_image']); ?>" alt="NID Back" class="kyc-preview-image">
                        </div>

                        <div class="form-actions">
                            <a href="<?= SITE_URL . '/' . e($user['nid_back_image']); ?>" target="_blank" class="btn-light">
                                View Full
                            </a>

                            <a href="<?= SITE_URL . '/' . e($user['nid_back_image']); ?>" download class="btn-primary">
                                Download
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">এই ইউজার ব্যাক NID submit করেনি।</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 24px;">
                <a href="<?= SITE_URL; ?>/admin/users.php" class="btn-light">সব ইউজারে ফিরে যান</a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>