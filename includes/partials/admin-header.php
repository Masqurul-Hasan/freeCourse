<header class="site-header admin-site-header">
    <div class="container">
        <div class="header-row">
            <a href="<?= SITE_URL; ?>/admin/dashboard.php" class="brand">
                <span class="brand-mark">AD</span>
                <span class="brand-text">Admin Panel</span>
            </a>

            <button class="menu-toggle" type="button" aria-label="মেনু খুলুন" id="adminMenuToggle">
                ☰
            </button>

            <nav class="main-nav admin-nav" id="adminMainNav">
                <a href="<?= SITE_URL; ?>/admin/dashboard.php">ড্যাশবোর্ড</a>
                <a href="<?= SITE_URL; ?>/admin/users.php">ইউজারস</a>
                <a href="<?= SITE_URL; ?>/admin/create-user.php">Create User</a>
                <a href="<?= SITE_URL; ?>/admin/pending-kyc.php">Pending KYC</a>
                <a href="<?= SITE_URL; ?>/admin/logout.php" class="nav-btn nav-btn-outline">লগআউট</a>
            </nav>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('adminMenuToggle');
    const nav = document.getElementById('adminMainNav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('is-open');
        });
    }
});
</script>