<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'Wallet Transactions';
$meta_description = 'Admin wallet ledger and transaction history';

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$source = trim($_GET['source'] ?? '');

/* =========================================================
   STATS
   ========================================================= */
$totalTransactions = (int) $pdo->query("SELECT COUNT(*) FROM wallet_transactions")->fetchColumn();
$totalCreditTransactions = (int) $pdo->query("SELECT COUNT(*) FROM wallet_transactions WHERE type = 'credit'")->fetchColumn();
$totalDebitTransactions = (int) $pdo->query("SELECT COUNT(*) FROM wallet_transactions WHERE type = 'debit'")->fetchColumn();

$stmt = $pdo->query("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) AS total_credit_amount,
        COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) AS total_debit_amount
    FROM wallet_transactions
");
$amountStats = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   FILTER QUERY
   ========================================================= */
$sql = "
    SELECT
        wt.id,
        wt.user_id,
        wt.amount,
        wt.type,
        wt.source,
        wt.reference_id,
        wt.description,
        wt.created_at,

        u.name,
        u.user_uid,
        u.phone,
        u.email

    FROM wallet_transactions wt
    INNER JOIN users u ON u.id = wt.user_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        u.name LIKE ?
        OR u.phone LIKE ?
        OR u.email LIKE ?
        OR u.user_uid LIKE ?
        OR wt.description LIKE ?
    )";

    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($type !== '') {
    $sql .= " AND wt.type = ?";
    $params[] = $type;
}

if ($source !== '') {
    $sql .= " AND wt.source = ?";
    $params[] = $source;
}

$sql .= " ORDER BY wt.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

function admin_wallet_source_label(string $source): string
{
    $map = [
        'signup_bonus'     => 'Signup Bonus',
        'referral_bonus'   => 'Referral Bonus',
        'withdraw'         => 'Withdraw',
        'admin_adjustment' => 'Admin Adjustment',
    ];

    return $map[$source] ?? ucfirst(str_replace('_', ' ', $source));
}

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<style>
.admin-wallet-shell {
    padding: 28px 0 48px;
}

.admin-wallet-hero {
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid #e8eef6;
    border-radius: 28px;
    padding: 30px;
    margin-bottom: 22px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
}

.admin-wallet-hero h1 {
    margin: 0 0 8px;
    font-size: clamp(30px, 3vw, 42px);
    line-height: 1.08;
    color: #0f172a;
    font-weight: 900;
}

.admin-wallet-hero p {
    margin: 0;
    color: #64748b;
    font-size: 15px;
    line-height: 1.7;
}

.admin-wallet-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.admin-wallet-stat-card {
    background: #fff;
    border: 1px solid #e8eef6;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.04);
}

.admin-wallet-stat-label {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 10px;
}

.admin-wallet-stat-value {
    font-size: clamp(26px, 2.6vw, 34px);
    line-height: 1.05;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 8px;
}

.admin-wallet-stat-note {
    font-size: 14px;
    color: #64748b;
    line-height: 1.6;
}

.admin-wallet-stat-value.credit {
    color: #15803d;
}

.admin-wallet-stat-value.debit {
    color: #dc2626;
}

.admin-wallet-card {
    background: #fff;
    border: 1px solid #e8eef6;
    border-radius: 28px;
    padding: 24px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
}

.admin-wallet-section-head {
    margin-bottom: 18px;
}

.admin-wallet-section-head h2 {
    margin: 0 0 8px;
    font-size: 28px;
    color: #0f172a;
    font-weight: 900;
}

.admin-wallet-section-head p {
    margin: 0;
    color: #64748b;
}

.admin-wallet-filter-form {
    margin-bottom: 20px;
}

.admin-wallet-filter-grid {
    display: grid;
    grid-template-columns: 1.5fr 0.8fr 1fr;
    gap: 14px;
}

.admin-wallet-input,
.admin-wallet-select {
    width: 100%;
    min-height: 52px;
    border-radius: 14px;
    border: 1px solid #dbe4ee;
    background: #fff;
    padding: 0 16px;
    font-size: 14px;
    color: #0f172a;
    outline: none;
}

.admin-wallet-input:focus,
.admin-wallet-select:focus {
    border-color: #93c5fd;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.10);
}

.admin-wallet-actions {
    display: flex;
    gap: 12px;
    margin-top: 14px;
    flex-wrap: wrap;
}

.admin-wallet-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    font-weight: 800;
    text-decoration: none;
    transition: 0.25s ease;
}

.admin-wallet-btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    border: none;
}

.admin-wallet-btn-primary:hover {
    color: #fff;
    transform: translateY(-1px);
}

.admin-wallet-btn-light {
    background: #fff;
    color: #0f172a;
    border: 1px solid #dbe4ee;
}

.admin-wallet-btn-light:hover {
    color: #0f172a;
    background: #f8fbff;
}

.admin-wallet-table-wrap {
    overflow-x: auto;
    border: 1px solid #e8eef6;
    border-radius: 20px;
}

.admin-wallet-table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
    background: #fff;
}

.admin-wallet-table thead th {
    background: #f8fbff;
    padding: 16px 18px;
    text-align: left;
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
    border-bottom: 1px solid #e8eef6;
    white-space: nowrap;
}

.admin-wallet-table tbody td {
    padding: 18px;
    font-size: 14px;
    color: #0f172a;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
}

.admin-wallet-table tbody tr:last-child td {
    border-bottom: none;
}

.admin-wallet-user strong {
    display: block;
    font-size: 15px;
    color: #0f172a;
    margin-bottom: 4px;
}

.admin-wallet-user small,
.admin-wallet-muted {
    color: #64748b;
    line-height: 1.6;
}

.admin-wallet-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88px;
    padding: 9px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

.admin-wallet-badge.credit {
    background: rgba(22, 163, 74, 0.10);
    color: #15803d;
}

.admin-wallet-badge.debit {
    background: rgba(220, 38, 38, 0.10);
    color: #b91c1c;
}

.admin-wallet-amount.credit {
    color: #15803d;
    font-weight: 900;
}

.admin-wallet-amount.debit {
    color: #dc2626;
    font-weight: 900;
}

.admin-wallet-source {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 800;
}

.admin-wallet-empty {
    text-align: center;
    padding: 54px 20px;
    color: #64748b;
    border: 1px dashed #d9e3ee;
    border-radius: 20px;
    background: linear-gradient(180deg, #fcfdff 0%, #f8fbff 100%);
}

@media (max-width: 1100px) {
    .admin-wallet-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .admin-wallet-filter-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 700px) {
    .admin-wallet-shell {
        padding: 20px 0 38px;
    }

    .admin-wallet-hero,
    .admin-wallet-card {
        border-radius: 22px;
        padding: 18px;
    }

    .admin-wallet-stats {
        grid-template-columns: 1fr;
    }

    .admin-wallet-filter-grid {
        grid-template-columns: 1fr;
    }

    .admin-wallet-actions {
        flex-direction: column;
    }

    .admin-wallet-btn {
        width: 100%;
    }
}
</style>

<main class="admin-wallet-shell">
    <div class="container">

        <section class="admin-wallet-hero">
            <h1>Wallet Transactions</h1>
            <p>
                সব user-এর wallet credit, debit, withdraw এবং admin adjustment history
                এখান থেকে review করতে পারবেন।
            </p>
        </section>

        <section class="admin-wallet-stats">
            <div class="admin-wallet-stat-card">
                <div class="admin-wallet-stat-label">Total Transactions</div>
                <div class="admin-wallet-stat-value"><?= $totalTransactions; ?></div>
                <div class="admin-wallet-stat-note">সব wallet ledger entry</div>
            </div>

            <div class="admin-wallet-stat-card">
                <div class="admin-wallet-stat-label">Credit Entries</div>
                <div class="admin-wallet-stat-value credit"><?= $totalCreditTransactions; ?></div>
                <div class="admin-wallet-stat-note">মোট credit transaction</div>
            </div>

            <div class="admin-wallet-stat-card">
                <div class="admin-wallet-stat-label">Debit Entries</div>
                <div class="admin-wallet-stat-value debit"><?= $totalDebitTransactions; ?></div>
                <div class="admin-wallet-stat-note">মোট debit transaction</div>
            </div>

            <div class="admin-wallet-stat-card">
                <div class="admin-wallet-stat-label">Net Summary</div>
                <div class="admin-wallet-stat-value">
                    ৳<?= number_format((float)$amountStats['total_credit_amount'] - (float)$amountStats['total_debit_amount'], 2); ?>
                </div>
                <div class="admin-wallet-stat-note">
                    Credit: ৳<?= number_format((float)$amountStats['total_credit_amount'], 2); ?> |
                    Debit: ৳<?= number_format((float)$amountStats['total_debit_amount'], 2); ?>
                </div>
            </div>
        </section>

        <section class="admin-wallet-card">
            <div class="admin-wallet-section-head">
                <h2>Ledger History</h2>
                <p>Search, filter এবং user-wise transaction review করুন</p>
            </div>

            <form method="GET" class="admin-wallet-filter-form">
                <div class="admin-wallet-filter-grid">
                    <input
                        type="text"
                        name="search"
                        class="admin-wallet-input"
                        placeholder="নাম / ফোন / ইমেইল / UID / description"
                        value="<?= e($search); ?>"
                    >

                    <select name="type" class="admin-wallet-select">
                        <option value="">সব Type</option>
                        <option value="credit" <?= $type === 'credit' ? 'selected' : ''; ?>>Credit</option>
                        <option value="debit" <?= $type === 'debit' ? 'selected' : ''; ?>>Debit</option>
                    </select>

                    <select name="source" class="admin-wallet-select">
                        <option value="">সব Source</option>
                        <option value="signup_bonus" <?= $source === 'signup_bonus' ? 'selected' : ''; ?>>Signup Bonus</option>
                        <option value="referral_bonus" <?= $source === 'referral_bonus' ? 'selected' : ''; ?>>Referral Bonus</option>
                        <option value="withdraw" <?= $source === 'withdraw' ? 'selected' : ''; ?>>Withdraw</option>
                        <option value="admin_adjustment" <?= $source === 'admin_adjustment' ? 'selected' : ''; ?>>Admin Adjustment</option>
                    </select>
                </div>

                <div class="admin-wallet-actions">
                    <button type="submit" class="admin-wallet-btn admin-wallet-btn-primary">Filter করুন</button>
                    <a href="<?= SITE_URL; ?>/admin/wallet-transactions.php" class="admin-wallet-btn admin-wallet-btn-light">Reset</a>
                </div>
            </form>

            <?php if (empty($transactions)): ?>
                <div class="admin-wallet-empty">
                    কোনো wallet transaction পাওয়া যায়নি।
                </div>
            <?php else: ?>
                <div class="admin-wallet-table-wrap">
                    <table class="admin-wallet-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Source</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $index => $tx): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>

                                    <td class="admin-wallet-user">
                                        <strong><?= e($tx['name']); ?></strong>
                                        <small><?= e($tx['user_uid']); ?></small>
                                    </td>

                                    <td class="admin-wallet-muted">
                                        <div><?= e($tx['phone']); ?></div>
                                        <small><?= e($tx['email'] ?: 'N/A'); ?></small>
                                    </td>

                                    <td>
                                        <span class="admin-wallet-badge <?= e($tx['type']); ?>">
                                            <?= e(ucfirst($tx['type'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="admin-wallet-amount <?= e($tx['type']); ?>">
                                            <?= $tx['type'] === 'credit' ? '+' : '-'; ?>৳<?= number_format((float)$tx['amount'], 2); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="admin-wallet-source">
                                            <?= e(admin_wallet_source_label($tx['source'])); ?>
                                        </span>
                                    </td>

                                    <td class="admin-wallet-muted"><?= e($tx['description'] ?: 'N/A'); ?></td>
                                    <td class="admin-wallet-muted"><?= e($tx['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>