<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

$userId = (int) $_SESSION['user_id'];

/* =========================================================
   USER DATA
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT id, name, wallet_balance, kyc_status, account_status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   WALLET STATS
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) AS total_credit,
        COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) AS total_debit
    FROM wallet_transactions
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$walletStats = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   TRANSACTION HISTORY
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT
        id,
        amount,
        type,
        source,
        reference_id,
        description,
        created_at
    FROM wallet_transactions
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'ওয়ালেট';
$meta_description = 'আপনার wallet balance এবং transaction history';

function wallet_source_label(string $source): string
{
    $map = [
        'signup_bonus'     => 'Signup Bonus',
        'referral_bonus'   => 'Referral Bonus',
        'withdraw'         => 'Withdraw',
        'admin_adjustment' => 'Admin Adjustment',
    ];

    return $map[$source] ?? ucfirst(str_replace('_', ' ', $source));
}

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<style>
.wallet-shell {
    padding: 36px 0 56px;
}

.wallet-hero {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 34px;
    background:
        radial-gradient(circle at top right, rgba(37, 99, 235, 0.14), transparent 32%),
        radial-gradient(circle at bottom left, rgba(59, 130, 246, 0.10), transparent 28%),
        linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid rgba(37, 99, 235, 0.08);
    box-shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
    margin-bottom: 22px;
}

.wallet-hero-grid {
    display: grid;
    grid-template-columns: 1.5fr 0.8fr;
    gap: 22px;
    align-items: center;
}

.wallet-eyebrow {
    margin: 0 0 10px;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #2563eb;
}

.wallet-title {
    margin: 0;
    font-size: clamp(30px, 4vw, 46px);
    line-height: 1.05;
    font-weight: 900;
    color: #0f172a;
}

.wallet-subtitle {
    margin: 14px 0 0;
    max-width: 720px;
    color: #5b6b85;
    font-size: 16px;
    line-height: 1.7;
}

.wallet-hero-side {
    display: flex;
    justify-content: flex-end;
}

.wallet-balance-box {
    width: 100%;
    max-width: 310px;
    border-radius: 24px;
    padding: 24px;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
    color: #fff;
    box-shadow: 0 18px 40px rgba(30, 64, 175, 0.22);
}

.wallet-balance-label {
    font-size: 13px;
    color: rgba(255,255,255,0.74);
    margin-bottom: 8px;
}

.wallet-balance-value {
    font-size: clamp(28px, 3vw, 40px);
    line-height: 1;
    font-weight: 900;
    letter-spacing: -0.02em;
    margin-bottom: 10px;
}

.wallet-balance-note {
    font-size: 14px;
    color: rgba(255,255,255,0.76);
    line-height: 1.6;
}

.wallet-flash {
    margin-bottom: 18px;
}

.wallet-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.wallet-stat-card {
    border-radius: 24px;
    padding: 22px;
    background: #fff;
    border: 1px solid #e7edf5;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.05);
}

.wallet-stat-label {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 10px;
}

.wallet-stat-value {
    font-size: clamp(24px, 2.4vw, 34px);
    line-height: 1.05;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 8px;
}

.wallet-stat-note {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
}

.wallet-stat-value.is-credit {
    color: #15803d;
}

.wallet-stat-value.is-debit {
    color: #dc2626;
}

.wallet-status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 10px 14px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.01em;
}

.wallet-status-pill.approved {
    background: rgba(22, 163, 74, 0.10);
    color: #15803d;
}

.wallet-status-pill.pending {
    background: rgba(245, 158, 11, 0.12);
    color: #b45309;
}

.wallet-status-pill.rejected {
    background: rgba(220, 38, 38, 0.10);
    color: #b91c1c;
}

.wallet-ledger-card {
    border-radius: 28px;
    padding: 26px;
    background: #fff;
    border: 1px solid #e7edf5;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
}

.wallet-section-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.wallet-section-head h2 {
    margin: 0;
    font-size: clamp(24px, 2vw, 32px);
    line-height: 1.1;
    color: #0f172a;
}

.wallet-section-head p {
    margin: 8px 0 0;
    color: #64748b;
    font-size: 15px;
}

.wallet-table-wrap {
    overflow-x: auto;
    border-radius: 22px;
    border: 1px solid #e7edf5;
    background: #fff;
}

.wallet-table {
    width: 100%;
    min-width: 860px;
    border-collapse: collapse;
}

.wallet-table thead th {
    background: #f8fbff;
    color: #64748b;
    font-size: 13px;
    font-weight: 800;
    text-align: left;
    padding: 16px 18px;
    border-bottom: 1px solid #e7edf5;
    white-space: nowrap;
}

.wallet-table tbody td {
    padding: 18px;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
    color: #0f172a;
    font-size: 14px;
}

.wallet-table tbody tr:last-child td {
    border-bottom: none;
}

.wallet-type-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88px;
    padding: 9px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 800;
}

.wallet-type-badge.credit {
    background: rgba(22, 163, 74, 0.10);
    color: #15803d;
}

.wallet-type-badge.debit {
    background: rgba(220, 38, 38, 0.10);
    color: #b91c1c;
}

.wallet-amount.credit {
    color: #15803d;
    font-weight: 900;
}

.wallet-amount.debit {
    color: #dc2626;
    font-weight: 900;
}

.wallet-source {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 800;
}

.wallet-description {
    color: #475569;
    line-height: 1.6;
    min-width: 220px;
}

.wallet-date {
    color: #64748b;
    white-space: nowrap;
}

.wallet-empty {
    text-align: center;
    padding: 56px 20px;
    border: 1px dashed #d7e1ec;
    border-radius: 22px;
    background: linear-gradient(180deg, #fcfdff 0%, #f8fbff 100%);
}

.wallet-empty h3 {
    margin: 0 0 10px;
    color: #0f172a;
    font-size: 24px;
}

.wallet-empty p {
    margin: 0;
    color: #64748b;
    line-height: 1.7;
}

.wallet-actions {
    margin-top: 22px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.wallet-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 50px;
    padding: 0 22px;
    border-radius: 14px;
    font-weight: 800;
    text-decoration: none;
    transition: 0.25s ease;
}

.wallet-btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.20);
}

.wallet-btn-primary:hover {
    transform: translateY(-1px);
    color: #fff;
}

.wallet-btn-light {
    background: #fff;
    color: #0f172a;
    border: 1px solid #dbe4ee;
}

.wallet-btn-light:hover {
    background: #f8fbff;
    color: #0f172a;
}

@media (max-width: 1100px) {
    .wallet-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .wallet-hero-grid {
        grid-template-columns: 1fr;
    }

    .wallet-hero-side {
        justify-content: flex-start;
    }
}

@media (max-width: 700px) {
    .wallet-shell {
        padding: 22px 0 40px;
    }

    .wallet-hero {
        padding: 22px;
        border-radius: 22px;
    }

    .wallet-grid {
        grid-template-columns: 1fr;
    }

    .wallet-stat-card,
    .wallet-ledger-card {
        border-radius: 20px;
        padding: 18px;
    }

    .wallet-actions {
        flex-direction: column;
    }

    .wallet-btn {
        width: 100%;
    }

    .wallet-table {
        min-width: 720px;
    }
}
</style>

<main class="wallet-shell">
    <div class="container">

        <?php if ($msg = getFlash('success')): ?>
            <div class="wallet-flash alert alert-success"><?= e($msg); ?></div>
        <?php endif; ?>

        <?php if ($msg = getFlash('error')): ?>
            <div class="wallet-flash alert alert-error"><?= e($msg); ?></div>
        <?php endif; ?>

        <section class="wallet-hero">
            <div class="wallet-hero-grid">
                <div>
                    <p class="wallet-eyebrow">Wallet Center</p>
                    <h1 class="wallet-title">আমার ওয়ালেট</h1>
                    <p class="wallet-subtitle">
                        এখানে আপনার বর্তমান balance, মোট credit-debit summary এবং সব wallet transaction
                        এক জায়গায় সুন্দরভাবে দেখা যাবে।
                    </p>
                </div>

                <div class="wallet-hero-side">
                    <div class="wallet-balance-box">
                        <div class="wallet-balance-label">Available Balance</div>
                        <div class="wallet-balance-value">৳<?= number_format((float)$user['wallet_balance'], 2); ?></div>
                        <div class="wallet-balance-note">
                            আপনার withdraw ও অন্যান্য wallet activity-এর জন্য available balance।
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="wallet-grid">
            <div class="wallet-stat-card">
                <div class="wallet-stat-label">Current Balance</div>
                <div class="wallet-stat-value">৳<?= number_format((float)$user['wallet_balance'], 2); ?></div>
                <div class="wallet-stat-note">বর্তমান available balance</div>
            </div>

            <div class="wallet-stat-card">
                <div class="wallet-stat-label">Total Credit</div>
                <div class="wallet-stat-value is-credit">৳<?= number_format((float)($walletStats['total_credit'] ?? 0), 2); ?></div>
                <div class="wallet-stat-note">মোট যোগ হওয়া amount</div>
            </div>

            <div class="wallet-stat-card">
                <div class="wallet-stat-label">Total Debit</div>
                <div class="wallet-stat-value is-debit">৳<?= number_format((float)($walletStats['total_debit'] ?? 0), 2); ?></div>
                <div class="wallet-stat-note">মোট কাটা amount</div>
            </div>

            <div class="wallet-stat-card">
                <div class="wallet-stat-label">KYC Status</div>
                <div style="margin-bottom: 8px;">
                    <?php
                    $kycClass = 'pending';
                    if ($user['kyc_status'] === 'approved') {
                        $kycClass = 'approved';
                    } elseif ($user['kyc_status'] === 'rejected') {
                        $kycClass = 'rejected';
                    }
                    ?>
                    <span class="wallet-status-pill <?= $kycClass; ?>">
                        <?= e(ucfirst($user['kyc_status'])); ?>
                    </span>
                </div>
                <div class="wallet-stat-note">Withdraw এর জন্য approved লাগবে</div>
            </div>
        </section>

        <section class="wallet-ledger-card">
            <div class="wallet-section-head">
                <div>
                    <h2>Transaction History</h2>
                    <p>আপনার wallet এর সব credit ও debit history এখানে দেখানো হচ্ছে</p>
                </div>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="wallet-empty">
                    <h3>এখনো কোনো transaction নেই</h3>
                    <p>আপনার wallet এ কোনো credit বা debit activity হলে এখানে automatically দেখা যাবে।</p>
                </div>
            <?php else: ?>
                <div class="wallet-table-wrap">
                    <table class="wallet-table">
                        <thead>
                            <tr>
                                <th>#</th>
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

                                    <td>
                                        <span class="wallet-type-badge <?= e($tx['type']); ?>">
                                            <?= e(ucfirst($tx['type'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="wallet-amount <?= e($tx['type']); ?>">
                                            <?= $tx['type'] === 'credit' ? '+' : '-'; ?>৳<?= number_format((float)$tx['amount'], 2); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="wallet-source"><?= e(wallet_source_label($tx['source'])); ?></span>
                                    </td>

                                    <td class="wallet-description"><?= e($tx['description'] ?: 'N/A'); ?></td>
                                    <td class="wallet-date"><?= e($tx['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="wallet-actions">
                <a href="<?= SITE_URL; ?>/withdraw.php" class="wallet-btn wallet-btn-primary">Withdraw করুন</a>
                <a href="<?= SITE_URL; ?>/dashboard.php" class="wallet-btn wallet-btn-light">ড্যাশবোর্ডে ফিরে যান</a>
            </div>
        </section>

    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>