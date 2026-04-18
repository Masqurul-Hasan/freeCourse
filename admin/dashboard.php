<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'এডমিন ড্যাশবোর্ড';
$meta_description = 'এডমিন ড্যাশবোর্ড';

/* =========================================================
   DASHBOARD STATS
   ========================================================= */
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingKyc = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE kyc_status = 'pending'")->fetchColumn();
$approvedKyc = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE kyc_status = 'approved'")->fetchColumn();
$rejectedKyc = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE kyc_status = 'rejected'")->fetchColumn();
$resubmitKyc = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE kyc_status = 'resubmit_required'")->fetchColumn();

/* =========================================================
   WITHDRAW STATS
   ========================================================= */
$totalWithdrawRequests = (int) $pdo->query("SELECT COUNT(*) FROM withdraw_requests")->fetchColumn();
$pendingWithdrawRequests = (int) $pdo->query("SELECT COUNT(*) FROM withdraw_requests WHERE status = 'pending'")->fetchColumn();
$paidWithdrawRequests = (int) $pdo->query("SELECT COUNT(*) FROM withdraw_requests WHERE status = 'paid'")->fetchColumn();
$rejectedWithdrawRequests = (int) $pdo->query("SELECT COUNT(*) FROM withdraw_requests WHERE status = 'rejected'")->fetchColumn();

/* =========================================================
   WALLET TRANSACTIONS STATS
   ========================================================= */
$totalWalletTransactions = (int) $pdo->query("SELECT COUNT(*) FROM wallet_transactions")->fetchColumn();
$totalWalletCredits = (int) $pdo->query("SELECT COUNT(*) FROM wallet_transactions WHERE type = 'credit'")->fetchColumn();
$totalWalletDebits = (int) $pdo->query("SELECT COUNT(*) FROM wallet_transactions WHERE type = 'debit'")->fetchColumn();

/* =========================================================
   RECENT ADMIN ACTIVITY
   ========================================================= */
$stmt = $pdo->query("
    SELECT
        al.id,
        al.admin_id,
        al.action,
        al.target_id,
        al.created_at,
        a.name AS admin_name,
        a.email AS admin_email
    FROM admin_logs al
    LEFT JOIN admins a ON a.id = al.admin_id
    ORDER BY al.id DESC
    LIMIT 5
");
$recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function admin_action_label(string $action): string
{
    $map = [
        'wallet_adjustment' => 'Wallet Adjustment',
        'withdraw_paid' => 'Withdraw Paid',
        'withdraw_rejected' => 'Withdraw Rejected',
    ];

    return $map[$action] ?? ucfirst(str_replace('_', ' ', $action));
}

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="admin-hero card">
            <div class="admin-hero-content">
                <div>
                    <p class="admin-eyebrow">Admin Panel</p>
                    <h1>স্বাগতম, <?= e($_SESSION['admin_name']); ?></h1>
                    <p class="admin-subtext">
                        এডমিন ইমেইল: <?= e($_SESSION['admin_email']); ?><br>
                        রোল: <?= e($_SESSION['admin_role']); ?>
                    </p>
                </div>

                <div class="admin-hero-actions">
                    <a href="<?= SITE_URL; ?>/admin/pending-kyc.php" class="btn-primary">Pending KYC দেখুন</a>
                    <a href="<?= SITE_URL; ?>/admin/withdraw-requests.php" class="btn-light">Withdraw Requests</a>
                    <a href="<?= SITE_URL; ?>/admin/wallet-transactions.php" class="btn-light">Wallet Ledger</a>
                    <a href="<?= SITE_URL; ?>/admin/users.php" class="btn-light">ইউজারস</a>
                    <a href="<?= SITE_URL; ?>/admin/create-user.php" class="btn-light">Create User</a>
                    <a href="<?= SITE_URL; ?>/admin/logout.php" class="btn-light">লগআউট</a>
                </div>
            </div>
        </div>

        <div class="admin-stats-grid">
            <div class="admin-stat-card card">
                <div class="admin-stat-label">মোট ইউজার</div>
                <div class="admin-stat-value"><?= $totalUsers; ?></div>
                <div class="admin-stat-note">সিস্টেমে মোট নিবন্ধিত ইউজার</div>
            </div>

            <div class="admin-stat-card card">
                <div class="admin-stat-label">Pending KYC</div>
                <div class="admin-stat-value"><?= $pendingKyc; ?></div>
                <div class="admin-stat-note">এখনো review বাকি</div>
            </div>

            <div class="admin-stat-card card">
                <div class="admin-stat-label">Approved KYC</div>
                <div class="admin-stat-value"><?= $approvedKyc; ?></div>
                <div class="admin-stat-note">অনুমোদিত ইউজার</div>
            </div>

            <div class="admin-stat-card card">
                <div class="admin-stat-label">Rejected KYC</div>
                <div class="admin-stat-value"><?= $rejectedKyc; ?></div>
                <div class="admin-stat-note">বাতিল করা KYC</div>
            </div>

            <div class="admin-stat-card card">
                <div class="admin-stat-label">Resubmit Required</div>
                <div class="admin-stat-value"><?= $resubmitKyc; ?></div>
                <div class="admin-stat-note">পুনরায় জমা দিতে হবে</div>
            </div>
        </div>

        <div class="admin-stats-grid" style="margin-top: 18px;">
            <div class="admin-stat-card card">
                <div class="admin-stat-label">Total Withdraw</div>
                <div class="admin-stat-value"><?= $totalWithdrawRequests; ?></div>
                <div class="admin-stat-note">মোট withdraw request</div>
            </div>

            <div class="admin-stat-card card">
                <div class="admin-stat-label">Pending Withdraw</div>
                <div class="admin-stat-value"><?= $pendingWithdrawRequests; ?></div>
                <div class="admin-stat-note">review বাকি আছে</div>
            </div>

            <div class="admin-stat-card card">
                <div class="admin-stat-label">Wallet Credits</div>
                <div class="admin-stat-value"><?= $totalWalletCredits; ?></div>
                <div class="admin-stat-note">মোট credit entry</div>
            </div>

            <div class="admin-stat-card card">
                <div class="admin-stat-label">Wallet Debits</div>
                <div class="admin-stat-value"><?= $totalWalletDebits; ?></div>
                <div class="admin-stat-note">মোট debit entry</div>
            </div>

            <div class="admin-stat-card card">
                <div class="admin-stat-label">Wallet Ledger Rows</div>
                <div class="admin-stat-value"><?= $totalWalletTransactions; ?></div>
                <div class="admin-stat-note">সব wallet transaction entry</div>
            </div>
        </div>

        <div class="admin-section-grid">
            <div class="card admin-panel-card">
                <div class="section-head">
                    <h2>Quick Actions</h2>
                    <p>গুরুত্বপূর্ণ এডমিন কাজ দ্রুত করুন</p>
                </div>

                <div class="admin-action-grid">
                    <a href="<?= SITE_URL; ?>/admin/pending-kyc.php" class="admin-action-item">
                        <span class="admin-action-title">Pending KYC Review</span>
                        <span class="admin-action-text">যেসব ইউজারের KYC review বাকি আছে সেগুলো দেখুন</span>
                    </a>

                    <a href="<?= SITE_URL; ?>/admin/withdraw-requests.php" class="admin-action-item">
                        <span class="admin-action-title">Withdraw Requests</span>
                        <span class="admin-action-text">Pending withdraw request review, paid mark এবং reject করুন</span>
                    </a>

                    <a href="<?= SITE_URL; ?>/admin/wallet-transactions.php" class="admin-action-item">
                        <span class="admin-action-title">Wallet Ledger</span>
                        <span class="admin-action-text">সব wallet credit / debit history review করুন</span>
                    </a>

                    <a href="<?= SITE_URL; ?>/admin/activity-logs.php" class="admin-action-item">
                        <span class="admin-action-title">Activity Logs</span>
                        <span class="admin-action-text">Admin action history এবং recent activity দেখুন</span>
                    </a>

                    <a href="<?= SITE_URL; ?>/admin/users.php" class="admin-action-item">
                        <span class="admin-action-title">Users Management</span>
                        <span class="admin-action-text">সব ইউজার দেখুন, সার্চ করুন, এডিট করুন</span>
                    </a>

                    <a href="<?= SITE_URL; ?>/admin/create-user.php" class="admin-action-item">
                        <span class="admin-action-title">Create User</span>
                        <span class="admin-action-text">এডমিন থেকে সরাসরি নতুন ইউজার তৈরি করুন</span>
                    </a>

                    <a href="<?= SITE_URL; ?>/admin/logout.php" class="admin-action-item">
                        <span class="admin-action-title">Logout</span>
                        <span class="admin-action-text">এডমিন সেশন থেকে বের হয়ে যান</span>
                    </a>
                </div>
            </div>

            <div class="card admin-panel-card">
                <div class="section-head">
                    <h2>System Overview</h2>
                    <p>বর্তমান সিস্টেম অবস্থা</p>
                </div>

                <div class="overview-list">
                    <div class="overview-item">
                        <span>Pending KYC</span>
                        <strong><?= $pendingKyc; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Approved KYC</span>
                        <strong><?= $approvedKyc; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Rejected KYC</span>
                        <strong><?= $rejectedKyc; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Resubmit Required</span>
                        <strong><?= $resubmitKyc; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Total Users</span>
                        <strong><?= $totalUsers; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Total Withdraw Requests</span>
                        <strong><?= $totalWithdrawRequests; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Pending Withdraw</span>
                        <strong><?= $pendingWithdrawRequests; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Paid Withdraw</span>
                        <strong><?= $paidWithdrawRequests; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Rejected Withdraw</span>
                        <strong><?= $rejectedWithdrawRequests; ?></strong>
                    </div>

                    <div class="overview-item">
                        <span>Total Wallet Transactions</span>
                        <strong><?= $totalWalletTransactions; ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card admin-panel-card" style="margin-top: 24px;">
            <div class="section-head">
                <h2>Recent Admin Activity</h2>
                <p>সাম্প্রতিক admin action history</p>
            </div>

            <?php if (empty($recentLogs)): ?>
                <div class="empty-state">এখনো কোনো admin activity পাওয়া যায়নি।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Target ID</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $index => $log): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td>
                                        <strong><?= e($log['admin_name'] ?: 'Unknown Admin'); ?></strong><br>
                                        <small><?= e($log['admin_email'] ?: 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-approved">
                                            <?= e(admin_action_label($log['action'])); ?>
                                        </span>
                                    </td>
                                    <td><?= e((string)$log['target_id']); ?></td>
                                    <td><?= e($log['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-actions" style="margin-top: 18px;">
                    <a href="<?= SITE_URL; ?>/admin/activity-logs.php" class="btn-light">সব Activity Logs দেখুন</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>