<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'Referral Payouts';
$meta_description = 'Referral payout management';

$status = trim($_GET['status'] ?? '');

$sql = "
    SELECT
        rp.*,
        referrer.name AS referrer_name,
        referrer.phone AS referrer_phone,
        referrer.email AS referrer_email,
        referrer.user_uid AS referrer_uid,

        referred.name AS referred_name,
        referred.phone AS referred_phone,
        referred.user_uid AS referred_uid
    FROM referral_payouts rp
    INNER JOIN users referrer ON referrer.id = rp.referrer_user_id
    INNER JOIN users referred ON referred.id = rp.referred_user_id
    WHERE 1=1
";

$params = [];

if ($status !== '' && in_array($status, ['pending', 'paid'], true)) {
    $sql .= " AND rp.status = ? ";
    $params[] = $status;
}

$sql .= " ORDER BY rp.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM referral_payouts WHERE status = 'pending'")->fetchColumn();
$paidCount = (int)$pdo->query("SELECT COUNT(*) FROM referral_payouts WHERE status = 'paid'")->fetchColumn();

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card admin-panel-card">
            <div class="section-head">
                <h1>Referral Payouts</h1>
                <p>Manual bKash referral payout manage করুন</p>
            </div>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <div class="stats-grid dashboard-stats-grid" style="margin-bottom: 20px;">
                <div class="info-card">
                    <h3>Pending</h3>
                    <p><?= $pendingCount; ?></p>
                </div>

                <div class="info-card">
                    <h3>Paid</h3>
                    <p><?= $paidCount; ?></p>
                </div>
            </div>

            <div class="form-actions" style="margin-bottom: 18px;">
                <a href="<?= SITE_URL; ?>/admin/referral-payouts.php" class="btn-light">All</a>
                <a href="<?= SITE_URL; ?>/admin/referral-payouts.php?status=pending" class="btn-light">Pending</a>
                <a href="<?= SITE_URL; ?>/admin/referral-payouts.php?status=paid" class="btn-light">Paid</a>
            </div>

            <?php if (empty($payouts)): ?>
                <div class="empty-state">কোনো referral payout পাওয়া যায়নি।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Referrer</th>
                                <th>Referred User</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Paid At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payouts as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>

                                    <td>
                                        <strong><?= e($row['referrer_name']); ?></strong><br>
                                        <small>
                                            UID: <?= e($row['referrer_uid']); ?><br>
                                            Phone: <?= e($row['referrer_phone']); ?><br>
                                            Email: <?= e($row['referrer_email'] ?: 'N/A'); ?>
                                        </small>
                                    </td>

                                    <td>
                                        <strong><?= e($row['referred_name']); ?></strong><br>
                                        <small>
                                            UID: <?= e($row['referred_uid']); ?><br>
                                            Phone: <?= e($row['referred_phone']); ?>
                                        </small>
                                    </td>

                                    <td>৳<?= number_format((float)$row['amount'], 2); ?></td>

                                    <td>
                                        <?php if ((string)$row['status'] === 'paid'): ?>
                                            <span class="status-badge status-approved">paid</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= e($row['created_at']); ?></td>
                                    <td><?= e($row['paid_at'] ?: 'N/A'); ?></td>

                                    <td>
                                        <?php if ((string)$row['status'] === 'pending'): ?>
                                            <form action="<?= SITE_URL; ?>/actions/admin/mark-referral-paid-action.php" method="POST" style="display:inline-block;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                                                <input type="hidden" name="payout_id" value="<?= (int)$row['id']; ?>">
                                                <button
                                                    type="submit"
                                                    class="btn-primary"
                                                    onclick="return confirm('আপনি কি bKash payment manually send করেছেন?');"
                                                >
                                                    Mark Paid
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?= SITE_URL; ?>/actions/admin/mark-referral-unpaid-action.php" method="POST" style="display:inline-block;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                                                <input type="hidden" name="payout_id" value="<?= (int)$row['id']; ?>">
                                                <button
                                                    type="submit"
                                                    class="btn-light"
                                                    onclick="return confirm('আপনি কি এই payout আবার pending/unpaid করতে চান?');"
                                                >
                                                    Mark Unpaid
                                                </button>
                                            </form>
                                        <?php endif; ?>
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