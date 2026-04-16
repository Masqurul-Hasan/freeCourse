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
    SELECT id, user_uid, name, phone, kyc_status, account_status
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

$page_title = 'ড্যাশবোর্ড';
$meta_description = 'ইউজার ড্যাশবোর্ড';

$stmt = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

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
                    <h3>KYC স্ট্যাটাস</h3>
                    <p><span class="status-badge status-approved"><?= e($_SESSION['kyc_status']); ?></span></p>
                </div>

                <div class="info-card">
                    <h3>অ্যাকাউন্ট স্ট্যাটাস</h3>
                    <p><?= e($_SESSION['account_status']); ?></p>
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
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>