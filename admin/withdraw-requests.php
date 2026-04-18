<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'Withdraw Requests';
$meta_description = 'Withdraw request management';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

/* =========================================================
   STATS
   ========================================================= */
$totalRequests = (int) $pdo->query("SELECT COUNT(*) FROM withdraw_requests")->fetchColumn();
$pendingRequests = (int) $pdo->query("SELECT COUNT(*) FROM withdraw_requests WHERE status = 'pending'")->fetchColumn();
$paidRequests = (int) $pdo->query("SELECT COUNT(*) FROM withdraw_requests WHERE status = 'paid'")->fetchColumn();
$rejectedRequests = (int) $pdo->query("SELECT COUNT(*) FROM withdraw_requests WHERE status = 'rejected'")->fetchColumn();

/* =========================================================
   LIST QUERY
   ========================================================= */
$sql = "
    SELECT
        wr.id,
        wr.user_id,
        wr.amount,
        wr.payment_method,
        wr.payment_number,
        wr.admin_note,
        wr.status,
        wr.requested_at,
        wr.processed_at,

        u.user_uid,
        u.name,
        u.phone,
        u.email,
        u.wallet_balance,
        u.kyc_status,
        u.account_status

    FROM withdraw_requests wr
    INNER JOIN users u ON u.id = wr.user_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        u.name LIKE ?
        OR u.phone LIKE ?
        OR u.email LIKE ?
        OR u.user_uid LIKE ?
        OR wr.payment_number LIKE ?
    )";

    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($status !== '') {
    $sql .= " AND wr.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY
    CASE
        WHEN wr.status = 'pending' THEN 1
        WHEN wr.status = 'paid' THEN 2
        WHEN wr.status = 'rejected' THEN 3
        ELSE 4
    END,
    wr.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">

        <div class="card admin-panel-card">
            <div class="section-head">
                <h1>Withdraw Requests</h1>
                <p>ইউজারদের withdraw request review, paid mark এবং reject করুন</p>
            </div>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <div class="admin-stats-grid" style="margin-bottom: 24px;">
                <div class="admin-stat-card card">
                    <div class="admin-stat-label">Total Requests</div>
                    <div class="admin-stat-value"><?= $totalRequests; ?></div>
                    <div class="admin-stat-note">সব withdraw request</div>
                </div>

                <div class="admin-stat-card card">
                    <div class="admin-stat-label">Pending</div>
                    <div class="admin-stat-value"><?= $pendingRequests; ?></div>
                    <div class="admin-stat-note">Review বাকি আছে</div>
                </div>

                <div class="admin-stat-card card">
                    <div class="admin-stat-label">Paid</div>
                    <div class="admin-stat-value"><?= $paidRequests; ?></div>
                    <div class="admin-stat-note">পেমেন্ট সম্পন্ন</div>
                </div>

                <div class="admin-stat-card card">
                    <div class="admin-stat-label">Rejected</div>
                    <div class="admin-stat-value"><?= $rejectedRequests; ?></div>
                    <div class="admin-stat-note">বাতিল করা হয়েছে</div>
                </div>
            </div>

            <form method="GET" action="" class="admin-filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="search">সার্চ</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?= e($search); ?>"
                            placeholder="নাম / ফোন / ইমেইল / UID / payment number">
                    </div>

                    <div class="form-group">
                        <label for="status">স্ট্যাটাস</label>
                        <select id="status" name="status">
                            <option value="">সব</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?= $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">ফিল্টার করুন</button>
                    <a href="<?= SITE_URL; ?>/admin/withdraw-requests.php" class="btn-light">রিসেট</a>
                </div>
            </form>

            <?php if (empty($requests)): ?>
                <div class="empty-state" style="margin-top: 20px;">
                    কোনো withdraw request পাওয়া যায়নি।
                </div>
            <?php else: ?>
                <div class="table-wrap" style="margin-top: 20px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ইউজার</th>
                                <th>যোগাযোগ</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Admin Note</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>

                                    <td>
                                        <strong><?= e($row['name']); ?></strong><br>
                                        <small><?= e($row['user_uid']); ?></small>
                                    </td>

                                    <td>
                                        <div><?= e($row['phone']); ?></div>
                                        <small><?= e($row['email'] ?: 'N/A'); ?></small>
                                    </td>

                                    <td>
                                        <strong>৳<?= number_format((float)$row['amount'], 2); ?></strong><br>
                                        <small>Wallet: ৳<?= number_format((float)$row['wallet_balance'], 2); ?></small>
                                    </td>

                                    <td>
                                        <div><?= e($row['payment_method']); ?></div>
                                        <small><?= e($row['payment_number']); ?></small>
                                    </td>

                                    <td>
                                        <?php
                                        $statusClass = 'status-pending';
                                        if ($row['status'] === 'paid') {
                                            $statusClass = 'status-approved';
                                        } elseif ($row['status'] === 'rejected') {
                                            $statusClass = 'status-rejected';
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass; ?>">
                                            <?= e(ucfirst($row['status'])); ?>
                                        </span>
                                    </td>

                                    <td><?= e($row['admin_note'] ?: 'N/A'); ?></td>

                                    <td>
                                        <div><?= e($row['requested_at'] ?: 'N/A'); ?></div>
                                        <?php if (!empty($row['processed_at'])): ?>
                                            <small style="display:block; margin-top:4px; color:#666;">
                                                Processed: <?= e($row['processed_at']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <div style="display:flex; gap:8px; flex-wrap:wrap;">

                                                <form action="<?= SITE_URL; ?>/actions/admin/mark-withdraw-paid-action.php" method="POST" onsubmit="return confirm('এই withdraw request paid mark করতে চান?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                                                    <input type="hidden" name="withdraw_id" value="<?= (int)$row['id']; ?>">
                                                    <input type="hidden" name="user_id" value="<?= (int)$row['user_id']; ?>">
                                                    <button type="submit" class="action-btn action-btn-edit">Mark Paid</button>
                                                </form>

                                                <form action="<?= SITE_URL; ?>/actions/admin/reject-withdraw-action.php" method="POST" onsubmit="return confirm('এই withdraw request reject করতে চান?');">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                                                    <input type="hidden" name="withdraw_id" value="<?= (int)$row['id']; ?>">
                                                    <input type="hidden" name="user_id" value="<?= (int)$row['user_id']; ?>">
                                                    <button type="submit" class="action-btn action-btn-delete">Reject</button>
                                                </form>

                                            </div>
                                        <?php else: ?>
                                            <span style="color:#999;">Processed</span>
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