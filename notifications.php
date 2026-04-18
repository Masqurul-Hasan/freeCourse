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
   USER INFO
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT id, name
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
   NOTIFICATION STATS
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM notifications
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$totalNotifications = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM notifications
    WHERE user_id = ? AND is_read = 0
");
$stmt->execute([$userId]);
$unreadNotifications = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM notifications
    WHERE user_id = ? AND is_read = 1
");
$stmt->execute([$userId]);
$readNotifications = (int) $stmt->fetchColumn();

/* =========================================================
   NOTIFICATION LIST
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT id, title, message, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Notifications';
$meta_description = 'আপনার সব notification';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<style>
.notification-shell {
    padding: 32px 0 50px;
}

.notification-hero {
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid #e7edf5;
    border-radius: 28px;
    padding: 28px;
    margin-bottom: 22px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
}

.notification-hero h1 {
    margin: 0 0 8px;
    font-size: clamp(30px, 3vw, 42px);
    line-height: 1.08;
    color: #0f172a;
    font-weight: 900;
}

.notification-hero p {
    margin: 0;
    color: #64748b;
    line-height: 1.7;
    font-size: 15px;
}

.notification-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.notification-stat-card {
    background: #fff;
    border: 1px solid #e7edf5;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.04);
}

.notification-stat-label {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 10px;
}

.notification-stat-value {
    font-size: clamp(26px, 2.6vw, 34px);
    font-weight: 900;
    line-height: 1.05;
    color: #0f172a;
    margin-bottom: 6px;
}

.notification-stat-note {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}

.notification-stat-value.unread {
    color: #d97706;
}

.notification-stat-value.read {
    color: #15803d;
}

.notification-card {
    background: #fff;
    border: 1px solid #e7edf5;
    border-radius: 28px;
    padding: 24px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
}

.notification-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 20px;
}

.notification-head h2 {
    margin: 0 0 8px;
    font-size: 28px;
    color: #0f172a;
    font-weight: 900;
}

.notification-head p {
    margin: 0;
    color: #64748b;
}

.notification-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.notification-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    font-weight: 800;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: 0.25s ease;
}

.notification-btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
}

.notification-btn-primary:hover {
    color: #fff;
    transform: translateY(-1px);
}

.notification-btn-light {
    background: #fff;
    color: #0f172a;
    border: 1px solid #dbe4ee;
}

.notification-btn-light:hover {
    background: #f8fbff;
    color: #0f172a;
}

.notification-list {
    display: grid;
    gap: 16px;
}

.notification-item {
    border: 1px solid #e7edf5;
    border-radius: 20px;
    padding: 20px;
    background: #fff;
    transition: 0.2s ease;
}

.notification-item.unread {
    background: linear-gradient(180deg, #fffdf7 0%, #ffffff 100%);
    border-color: #fde7b0;
}

.notification-item.read {
    opacity: 0.92;
}

.notification-item-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.notification-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.notification-title {
    margin: 0;
    font-size: 18px;
    color: #0f172a;
    font-weight: 800;
}



.notification-badge.unread {
    background: rgba(245, 158, 11, 0.12);
    color: #b45309;
}

.notification-badge.read {
    background: rgba(22, 163, 74, 0.10);
    color: #15803d;
}

.notification-date {
    color: #64748b;
    font-size: 13px;
    white-space: nowrap;
}

.notification-message {
    color: #475569;
    font-size: 15px;
    line-height: 1.75;
    margin-bottom: 14px;
}

.notification-item-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.notification-empty {
    text-align: center;
    padding: 56px 20px;
    border: 1px dashed #d7e1ec;
    border-radius: 22px;
    background: linear-gradient(180deg, #fcfdff 0%, #f8fbff 100%);
}

.notification-empty h3 {
    margin: 0 0 10px;
    color: #0f172a;
    font-size: 24px;
}

.notification-empty p {
    margin: 0;
    color: #64748b;
    line-height: 1.7;
}

@media (max-width: 900px) {
    .notification-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .notification-shell {
        padding: 22px 0 40px;
    }

    .notification-hero,
    .notification-card {
        border-radius: 22px;
        padding: 18px;
    }

    .notification-actions {
        flex-direction: column;
        width: 100%;
    }

    .notification-btn {
        width: 100%;
    }
}
</style>

<main class="notification-shell">
    <div class="container">

        <?php if ($msg = getFlash('success')): ?>
            <div class="alert alert-success" style="margin-bottom:18px;"><?= e($msg); ?></div>
        <?php endif; ?>

        <?php if ($msg = getFlash('error')): ?>
            <div class="alert alert-error" style="margin-bottom:18px;"><?= e($msg); ?></div>
        <?php endif; ?>

        <section class="notification-hero">
            <h1>Notifications</h1>
            <p>
                আপনার account, wallet, withdraw এবং system update সম্পর্কিত সব notification
                এখানে এক জায়গায় দেখতে পারবেন।
            </p>
        </section>

        <section class="notification-stats">
            <div class="notification-stat-card">
                <div class="notification-stat-label">Total Notifications</div>
                <div class="notification-stat-value"><?= $totalNotifications; ?></div>
                <div class="notification-stat-note">আপনার সব notification</div>
            </div>

            <div class="notification-stat-card">
                <div class="notification-stat-label">Unread</div>
                <div class="notification-stat-value unread"><?= $unreadNotifications; ?></div>
                <div class="notification-stat-note">এখনো পড়া হয়নি</div>
            </div>

            <div class="notification-stat-card">
                <div class="notification-stat-label">Read</div>
                <div class="notification-stat-value read"><?= $readNotifications; ?></div>
                <div class="notification-stat-note">পড়া হয়েছে</div>
            </div>
        </section>

        <section class="notification-card">
            <div class="notification-head">
                <div>
                    <h2>Recent Notifications</h2>
                    <p>আপনার account activity গুলো এখান থেকে review করুন</p>
                </div>

                <div class="notification-actions">
                    <form action="<?= SITE_URL; ?>/actions/user/mark-all-notifications-read.php" method="POST">
                        <button type="submit" class="notification-btn notification-btn-primary">
                            সবগুলো Read করুন
                        </button>
                    </form>

                    <a href="<?= SITE_URL; ?>/dashboard.php" class="notification-btn notification-btn-light">
                        ড্যাশবোর্ডে ফিরে যান
                    </a>
                </div>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="notification-empty">
                    <h3>এখনো কোনো notification নেই</h3>
                    <p>System activity হলে এখানে notification দেখা যাবে।</p>
                </div>
            <?php else: ?>
                <div class="notification-list">
                    <?php foreach ($notifications as $item): ?>
                        <div class="notification-item <?= (int)$item['is_read'] === 0 ? 'unread' : 'read'; ?>">
                            <div class="notification-item-top">
                                <div class="notification-title-wrap">
                                    <h3 class="notification-title"><?= e($item['title']); ?></h3>

                                    <?php if ((int)$item['is_read'] === 0): ?>
                                        <span class="notification-badge unread">Unread</span>
                                    <?php else: ?>
                                        <span class="notification-badge read">Read</span>
                                    <?php endif; ?>
                                </div>

                                <div class="notification-date"><?= e($item['created_at']); ?></div>
                            </div>

                            <div class="notification-message">
                                <?= e($item['message']); ?>
                            </div>

                            <?php if ((int)$item['is_read'] === 0): ?>
                                <div class="notification-item-actions">
                                    <form action="<?= SITE_URL; ?>/actions/user/mark-notification-read.php" method="POST">
                                        <input type="hidden" name="notification_id" value="<?= (int)$item['id']; ?>">
                                        <button type="submit" class="notification-btn notification-btn-light">
                                            Mark as Read
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>