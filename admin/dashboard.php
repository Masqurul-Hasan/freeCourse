<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'এডমিন ড্যাশবোর্ড';
$meta_description = 'এডমিন ড্যাশবোর্ড';

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingKyc = (int) $pdo->query("SELECT COUNT(*) FROM kyc_submissions WHERE status = 'pending'")->fetchColumn();
$approvedKyc = (int) $pdo->query("SELECT COUNT(*) FROM kyc_submissions WHERE status = 'approved'")->fetchColumn();
$rejectedKyc = (int) $pdo->query("SELECT COUNT(*) FROM kyc_submissions WHERE status = 'rejected'")->fetchColumn();
$resubmitKyc = (int) $pdo->query("SELECT COUNT(*) FROM kyc_submissions WHERE status = 'resubmit_required'")->fetchColumn();

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
                        এডমিন ইমেইল: <?= e($_SESSION['admin_email']); ?> <br>
                        রোল: <?= e($_SESSION['admin_role']); ?>
                    </p>
                </div>

                <div class="admin-hero-actions">
                    <a href="<?= SITE_URL; ?>/admin/pending-kyc.php" class="btn-primary">Pending KYC দেখুন</a>
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

                    <a href="<?= SITE_URL; ?>/admin/pending-kyc.php" class="admin-action-item">
                        <span class="admin-action-title">Approve / Reject KYC</span>
                        <span class="admin-action-text">KYC status update করুন</span>
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
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>