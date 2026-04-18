<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   Refresh user
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT id, user_uid, name, phone, wallet_balance, kyc_status, account_status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_unset();
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

$_SESSION['user_uid'] = $user['user_uid'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_phone'] = $user['phone'];
$_SESSION['kyc_status'] = $user['kyc_status'];
$_SESSION['account_status'] = $user['account_status'];

/* =========================================================
   Get withdraw history
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM withdraw_requests
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$withdrawRows = $stmt->fetchAll();

$page_title = 'উইথড্র হিস্টোরি';
$meta_description = 'আপনার withdraw request history';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">

        <?php if ($msg = getFlash('success')): ?>
            <div class="alert alert-success"><?= e($msg); ?></div>
        <?php endif; ?>

        <?php if ($msg = getFlash('error')): ?>
            <div class="alert alert-error"><?= e($msg); ?></div>
        <?php endif; ?>

        <section class="withdraw-hero card">
            <div class="withdraw-hero-content">
                <div>
                    <p class="withdraw-eyebrow">Withdraw History</p>
                    <h1>উইথড্র হিস্টোরি</h1>
                    <p class="withdraw-hero-text">
                        আপনার সব withdraw request এবং সেগুলোর বর্তমান স্ট্যাটাস এখানে দেখা যাবে।
                    </p>
                </div>

                <div class="withdraw-hero-balance">
                    <span class="withdraw-balance-label">Current Balance</span>
                    <strong>৳<?= number_format((float)$user['wallet_balance'], 2); ?></strong>
                </div>
            </div>
        </section>

        <div class="card admin-panel-card">
            <div class="section-head">
                <h2>Withdraw Requests</h2>
                <p>আপনার জমা দেওয়া সব withdraw request এর তালিকা</p>
            </div>

            <?php if (empty($withdrawRows)): ?>
                <div class="empty-state">
                    আপনি এখনো কোনো withdraw request করেননি।
                </div>
            <?php else: ?>
                <div class="table-wrap" style="margin-top: 20px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Number</th>
                                <th>Status</th>
                                <th>Note</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawRows as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>

                                    <td>৳<?= number_format((float)$row['amount'], 2); ?></td>

                                    <td><?= e($row['payment_method']); ?></td>

                                    <td><?= e($row['payment_number']); ?></td>

                                    <td>
                                        <?php
                                        $statusClass = 'status-pending';
                                        if ($row['status'] === 'approved') $statusClass = 'status-approved';
                                        if ($row['status'] === 'paid') $statusClass = 'status-approved';
                                        if ($row['status'] === 'rejected') $statusClass = 'status-rejected';
                                        ?>
                                        <span class="status-badge <?= $statusClass; ?>">
                                            <?= e($row['status']); ?>
                                        </span>
                                    </td>

                                    <td><?= e($row['note'] ?: 'N/A'); ?></td>

                                    <td>
                                        <?=
                                            e(
                                                $row['requested_at']
                                                ?? $row['created_at']
                                                ?? 'N/A'
                                            );
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="form-actions" style="margin-top: 24px;">
                <a href="<?= SITE_URL; ?>/withdraw.php" class="btn-primary">নতুন Withdraw করুন</a>
                <a href="<?= SITE_URL; ?>/dashboard.php" class="btn-light">ড্যাশবোর্ডে ফিরে যান</a>
            </div>
        </div>

    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>