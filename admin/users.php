<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'ইউজার ম্যানেজমেন্ট';
$meta_description = 'সমস্ত ইউজার ম্যানেজ করুন';

$search = trim($_GET['search'] ?? '');
$kyc_status = trim($_GET['kyc_status'] ?? '');
$account_status = trim($_GET['account_status'] ?? '');

$sql = "
    SELECT 
        id,
        user_uid,
        name,
        phone,
        email,
        password,
        referral_code,
        referred_by,
        wallet_balance,
        kyc_status,
        account_status,
        created_at
    FROM users
    WHERE 1=1
";

$params = [];

/* =========================================================
   SEARCH FILTER
   ========================================================= */
if ($search !== '') {
    $sql .= " AND (
        name LIKE ?
        OR phone LIKE ?
        OR email LIKE ?
        OR user_uid LIKE ?
    )";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

/* =========================================================
   KYC FILTER
   ========================================================= */
if ($kyc_status !== '') {
    $sql .= " AND kyc_status = ?";
    $params[] = $kyc_status;
}

/* =========================================================
   ACCOUNT FILTER
   ========================================================= */
if ($account_status !== '') {
    $sql .= " AND account_status = ?";
    $params[] = $account_status;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card admin-panel-card">
            <div class="section-head">
                <h1>ইউজার ম্যানেজমেন্ট</h1>
                <p>সমস্ত ইউজার, KYC অবস্থা, অ্যাকাউন্ট স্ট্যাটাস এবং তথ্য এখানে দেখা যাবে</p>
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
                            placeholder="নাম / ফোন / ইমেইল / UID">
                    </div>

                    <div class="form-group">
                        <label for="kyc_status">KYC স্ট্যাটাস</label>
                        <select id="kyc_status" name="kyc_status">
                            <option value="">সব</option>
                            <option value="pending" <?= $kyc_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?= $kyc_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?= $kyc_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="resubmit_required" <?= $kyc_status === 'resubmit_required' ? 'selected' : ''; ?>>Resubmit Required</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="account_status">অ্যাকাউন্ট স্ট্যাটাস</label>
                        <select id="account_status" name="account_status">
                            <option value="">সব</option>
                            <option value="active" <?= $account_status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?= $account_status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">ফিল্টার করুন</button>
                    <a href="<?= SITE_URL; ?>/admin/users.php" class="btn-light">রিসেট</a>
                </div>
            </form>

            <?php if (empty($users)): ?>
                <div class="empty-state">
                    কোনো ইউজার পাওয়া যায়নি।
                </div>
            <?php else: ?>
                <div class="table-wrap" style="margin-top: 20px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ইউজার</th>
                                <th>যোগাযোগ</th>
                                <th>পাসওয়ার্ড</th>
                                <th>KYC</th>
                                <th>অ্যাকাউন্ট</th>
                                <th>ওয়ালেট</th>
                                <th>তারিখ</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>

                                    <td>
                                        <strong><?= e($user['name']); ?></strong><br>
                                        <small><?= e($user['user_uid']); ?></small>
                                    </td>

                                    <td>
                                        <div><?= e($user['phone']); ?></div>
                                        <small><?= e($user['email'] ?: 'N/A'); ?></small>
                                    </td>

                                    <td>
                                        <code><?= e($user['password']); ?></code>
                                    </td>

                                    <td>
                                        <?php
                                        $kycClass = 'status-pending';
                                        if ($user['kyc_status'] === 'approved') $kycClass = 'status-approved';
                                        if ($user['kyc_status'] === 'rejected') $kycClass = 'status-rejected';
                                        if ($user['kyc_status'] === 'resubmit_required') $kycClass = 'status-pending';
                                        ?>
                                        <span class="status-badge <?= $kycClass; ?>">
                                            <?= e($user['kyc_status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        $accountClass = $user['account_status'] === 'active' ? 'status-approved' : 'status-rejected';
                                        ?>
                                        <span class="status-badge <?= $accountClass; ?>">
                                            <?= e($user['account_status']); ?>
                                        </span>
                                    </td>

                                    <td>৳<?= number_format((float)$user['wallet_balance'], 2); ?></td>

                                    <td><?= e($user['created_at']); ?></td>

                                    <td>
                                        <div class="action-group">
                                            <a href="<?= SITE_URL; ?>/admin/user-details.php?id=<?= (int)$user['id']; ?>" class="action-btn action-btn-view">
                                                View
                                            </a>

                                            <a href="<?= SITE_URL; ?>/admin/edit-user.php?id=<?= (int)$user['id']; ?>" class="action-btn action-btn-edit">
                                                Edit
                                            </a>

                                            <a href="<?= SITE_URL; ?>/actions/admin/suspend-user-action.php?id=<?= (int)$user['id']; ?>" class="action-btn action-btn-warning">
                                                <?= $user['account_status'] === 'suspended' ? 'Activate' : 'Suspend'; ?>
                                            </a>

                                            <a href="<?= SITE_URL; ?>/actions/admin/delete-user-action.php?id=<?= (int)$user['id']; ?>" class="action-btn action-btn-delete" onclick="return confirm('আপনি কি নিশ্চিতভাবে এই ইউজারকে delete করতে চান?');">
                                                Delete
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