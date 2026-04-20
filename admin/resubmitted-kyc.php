<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'Resubmitted KYC';
$meta_description = 'Resubmitted KYC list';

/* =====================================================
   Show only resubmitted pending KYC
   attempt_no > 1 means user submitted again
   ===================================================== */
$stmt = $pdo->query("
    SELECT
        u.id AS user_id,
        u.name,
        u.phone,
        u.email,
        u.user_uid,
        u.kyc_status,

        k.id AS kyc_id,
        k.nid_number,
        k.date_of_birth,
        k.bkash_number,
        k.nid_front_image,
        k.nid_back_image,
        k.status,
        k.attempt_no,
        k.submitted_at,
        k.updated_at

    FROM users u

    INNER JOIN kyc_submissions k
        ON k.id = (
            SELECT ks.id
            FROM kyc_submissions ks
            WHERE ks.user_id = u.id
              AND ks.status = 'pending'
              AND ks.attempt_no > 1
            ORDER BY ks.id DESC
            LIMIT 1
        )

    WHERE k.status = 'pending'
      AND k.attempt_no > 1

    ORDER BY k.id DESC
");

$kycRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card" style="padding: 24px;">
            <div class="section-head">
                <h1>Resubmitted KYC তালিকা</h1>
                <p>যেসব ইউজার পুনরায় KYC জমা দিয়েছে, সেগুলো এখানে দেখানো হচ্ছে</p>
            </div>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if (empty($kycRows)): ?>
                <div class="empty-state">
                    কোনো resubmitted KYC পাওয়া যায়নি।
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ইউজার</th>
                                <th>মোবাইল</th>
                                <th>NID নাম্বার</th>
                                <th>বিকাশ</th>
                                <th>Attempt</th>
                                <th>স্ট্যাটাস</th>
                                <th>জমা সময়</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kycRows as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>

                                    <td>
                                        <strong><?= e($row['name']); ?></strong><br>
                                        <small><?= e($row['user_uid']); ?></small>
                                    </td>

                                    <td><?= e($row['phone']); ?></td>

                                    <td><?= e($row['nid_number'] ?: 'No KYC'); ?></td>

                                    <td><?= e($row['bkash_number'] ?: 'No KYC'); ?></td>

                                    <td>
                                        <span class="status-badge status-warning">
                                            Attempt <?= (int) $row['attempt_no']; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status-badge status-pending">
                                            <?= e($row['status']); ?>
                                        </span>
                                    </td>

                                    <td><?= e($row['submitted_at'] ?: 'Not Submitted'); ?></td>

                                    <td>
                                        <div class="action-group">
                                            <a href="<?= SITE_URL; ?>/admin/kyc-details.php?user_id=<?= (int)$row['user_id']; ?>" class="action-btn action-btn-view">
                                                Review
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>