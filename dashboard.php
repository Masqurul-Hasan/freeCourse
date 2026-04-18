<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   REFRESH USER SESSION FROM DATABASE
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT id, user_uid, name, phone, wallet_balance, kyc_status, account_status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

$_SESSION['user_uid'] = $user['user_uid'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_phone'] = $user['phone'];
$_SESSION['wallet_balance'] = $user['wallet_balance'];
$_SESSION['kyc_status'] = $user['kyc_status'];
$_SESSION['account_status'] = $user['account_status'];

$page_title = 'ড্যাশবোর্ড';
$meta_description = 'ইউজার ড্যাশবোর্ড';

/* =========================================================
   RECENT NOTIFICATIONS
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   UNREAD NOTIFICATION COUNT
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = ? AND is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$unreadNotificationCount = (int) $stmt->fetchColumn();

/* =========================================================
   WITHDRAW REQUEST COUNT
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM withdraw_requests
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalWithdrawRequests = (int) $stmt->fetchColumn();

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

        <?php if ($_SESSION['kyc_status'] === 'pending'): ?>
            <div class="card status-screen-card">
                <h2>আপনার KYC যাচাই চলছে</h2>
                <p>আপনার তথ্য সফলভাবে জমা হয়েছে।</p>
                <p>এডমিন যাচাই করার পর আপনি ড্যাশবোর্ড ব্যবহার করতে পারবেন।</p>

                <?php if (!empty($notifications)): ?>
                    <div class="mini-note-list">
                        <?php foreach ($notifications as $note): ?>
                            <div class="mini-note-item">
                                <strong><?= e($note['title']); ?></strong>
                                <span><?= e($note['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($_SESSION['kyc_status'] === 'resubmit_required'): ?>
            <div class="card status-screen-card">
                <h2>KYC পুনরায় জমা দিতে হবে</h2>
                <p>এডমিন আপনার KYC-তে সমস্যা পেয়েছেন। সংশোধন করে আবার জমা দিন।</p>

                <div class="form-actions" style="justify-content:center; margin-top: 18px;">
                    <a href="<?= SITE_URL; ?>/resubmit-kyc.php" class="btn-primary">পুনরায় KYC জমা দিন</a>
                </div>

                <?php if (!empty($notifications)): ?>
                    <div class="mini-note-list">
                        <?php foreach ($notifications as $note): ?>
                            <div class="mini-note-item">
                                <strong><?= e($note['title']); ?></strong>
                                <span><?= e($note['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($_SESSION['kyc_status'] === 'rejected'): ?>
            <div class="card status-screen-card">
                <h2>আপনার KYC বাতিল হয়েছে</h2>
                <p>এডমিন আপনার তথ্য গ্রহণ করেননি।</p>
                <p>প্রয়োজনে এডমিনের সাথে যোগাযোগ করুন।</p>

                <?php if (!empty($notifications)): ?>
                    <div class="mini-note-list">
                        <?php foreach ($notifications as $note): ?>
                            <div class="mini-note-item">
                                <strong><?= e($note['title']); ?></strong>
                                <span><?= e($note['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="card dashboard-welcome-card">
                <h1>স্বাগতম, <?= e($_SESSION['user_name']); ?></h1>
                <p>অভিনন্দন! আপনার অ্যাকাউন্ট অনুমোদিত হয়েছে। এখন আপনি ড্যাশবোর্ড ব্যবহার করতে পারবেন।</p>
            </div>

            <div class="stats-grid dashboard-stats-grid">
                <div class="info-card">
                    <h3>ইউজার UID</h3>
                    <p><?= e($_SESSION['user_uid']); ?></p>
                </div>

                <div class="info-card">
                    <h3>ওয়ালেট ব্যালেন্স</h3>
                    <p>৳<?= number_format((float)$_SESSION['wallet_balance'], 2); ?></p>
                </div>

                <div class="info-card">
                    <h3>KYC স্ট্যাটাস</h3>
                    <p><span class="status-badge status-approved"><?= e($_SESSION['kyc_status']); ?></span></p>
                </div>

                <div class="info-card">
                    <h3>অ্যাকাউন্ট স্ট্যাটাস</h3>
                    <p><?= e($_SESSION['account_status']); ?></p>
                </div>
            </div>

            <div class="card dashboard-notification-card" style="margin-bottom: 24px;">
                <div class="section-head">
                    <h2>Quick Access</h2>
                    <p>গুরুত্বপূর্ণ user action গুলো এখান থেকে দ্রুত access করুন</p>
                </div>

                <div class="stats-grid dashboard-stats-grid">
                    <div class="info-card">
                        <h3>Wallet</h3>
                        <p>আপনার wallet balance এবং সব transaction দেখুন</p>
                        <div class="form-actions" style="margin-top: 14px;">
                            <a href="<?= SITE_URL; ?>/wallet.php" class="btn-primary">Wallet দেখুন</a>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>Withdraw</h3>
                        <p>নতুন withdraw request পাঠান</p>
                        <div class="form-actions" style="margin-top: 14px;">
                            <a href="<?= SITE_URL; ?>/withdraw.php" class="btn-primary">Withdraw করুন</a>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>Withdraw History</h3>
                        <p>আপনার আগের withdraw request গুলোর status দেখুন</p>
                        <div class="form-actions" style="margin-top: 14px;">
                            <a href="<?= SITE_URL; ?>/withdraw-history.php" class="btn-primary">History দেখুন</a>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>Notifications</h3>
                        <p>
                            আপনার account, wallet এবং withdraw notification দেখুন
                            <?php if ($unreadNotificationCount > 0): ?>
                                <br><strong><?= $unreadNotificationCount; ?> টি unread</strong>
                            <?php endif; ?>
                        </p>
                        <div class="form-actions" style="margin-top: 14px;">
                            <a href="<?= SITE_URL; ?>/notifications.php" class="btn-primary">Notifications দেখুন</a>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>Total Withdraw Requests</h3>
                        <p><?= $totalWithdrawRequests; ?> টি request পাওয়া গেছে</p>
                        <div class="form-actions" style="margin-top: 14px;">
                            <a href="<?= SITE_URL; ?>/withdraw-history.php" class="btn-light">সব request</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($notifications)): ?>
                <div class="card dashboard-notification-card">
                    <div class="section-head">
                        <h2>সাম্প্রতিক নোটিফিকেশন</h2>
                    </div>

                    <div class="mini-note-list">
                        <?php foreach ($notifications as $note): ?>
                            <div class="mini-note-item">
                                <strong><?= e($note['title']); ?></strong>
                                <span><?= e($note['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-actions" style="margin-top: 18px;">
                        <a href="<?= SITE_URL; ?>/notifications.php" class="btn-light">সব নোটিফিকেশন দেখুন</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>