<?php
$unreadNotificationCount = 0;

if (!empty($_SESSION['user_id'])) {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$_SESSION['user_id']]);

    $unreadNotificationCount = (int) $stmt->fetchColumn();
}
?>

<header class="site-header">
    <div class="container">
        <div class="header-row">

            <a href="<?= SITE_URL; ?>/index.php" class="brand brand-logo-only">
                <img
                    src="<?= SITE_LOGO; ?>"
                    alt="<?= e(SITE_NAME); ?>"
                    class="brand-logo"
                >
            </a>

            <button class="menu-toggle" type="button" aria-label="মেনু খুলুন" id="menuToggle">
                ☰
            </button>

            <nav class="main-nav" id="mainNav">
                <a href="<?= SITE_URL; ?>/index.php">হোম</a>
                <a href="<?= SITE_URL; ?>/about.php">আমাদের সম্পর্কে</a>
                <a href="<?= SITE_URL; ?>/contact.php">যোগাযোগ</a>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="<?= SITE_URL; ?>/dashboard.php">ড্যাশবোর্ড</a>
                    <a href="<?= SITE_URL; ?>/tasks.php">Tasks</a>
                    <a href="<?= SITE_URL; ?>/wallet.php">ওয়ালেট</a>
                    <a href="<?= SITE_URL; ?>/withdraw.php">উইথড্র</a>
                    <a href="<?= SITE_URL; ?>/withdraw-history.php">হিস্টোরি</a>

                    <a href="<?= SITE_URL; ?>/notifications.php" class="notification-nav">
                        নোটিফিকেশন
                        <?php if ($unreadNotificationCount > 0): ?>
                            <span class="notification-badge">
                                <?= $unreadNotificationCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <a href="<?= SITE_URL; ?>/logout.php" class="nav-btn nav-btn-outline">লগআউট</a>
                <?php else: ?>
                    <a href="<?= SITE_URL; ?>/login.php" class="nav-btn nav-btn-outline">লগইন</a>
                    <a href="<?= SITE_URL; ?>/register.php" class="nav-btn nav-btn-primary">রেজিস্টার</a>
                <?php endif; ?>
            </nav>

        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('menuToggle');
    const nav = document.getElementById('mainNav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('is-open');
        });
    }
});
</script>