<header class="site-header admin-site-header">
    <div class="container">
        <div class="header-row">

            <a href="<?= SITE_URL; ?>/admin/dashboard.php" class="brand brand-logo-only">
                <img
                    src="<?= SITE_LOGO; ?>"
                    alt="<?= e(SITE_NAME); ?>"
                    class="brand-logo">
            </a>

            <button class="menu-toggle" type="button" aria-label="মেনু খুলুন" id="adminMenuToggle">
                ☰
            </button>

            <nav class="main-nav admin-nav" id="adminMainNav">
                <a href="<?= SITE_URL; ?>/admin/dashboard.php">ড্যাশবোর্ড</a>
                <a href="<?= SITE_URL; ?>/admin/users.php">ইউজারস</a>
                <a href="<?= SITE_URL; ?>/admin/tasks.php">Tasks</a>
                <a href="<?= SITE_URL; ?>/admin/withdraw-requests.php">Withdraw</a>
                <a href="<?= SITE_URL; ?>/admin/pending-kyc.php">KYC</a>

                <div class="admin-nav-dropdown" id="adminMoreDropdown">
                    <button type="button" class="admin-nav-dropdown-toggle" id="adminMoreToggle">
                        More ▾
                    </button>

                    <div class="admin-nav-dropdown-menu" id="adminMoreMenu">
                        <a href="<?= SITE_URL; ?>/admin/task-submissions.php">Task Submissions</a>
                        <a href="<?= SITE_URL; ?>/admin/wallet-transactions.php">Wallet Ledger</a>
                        <a href="<?= SITE_URL; ?>/admin/referral-payouts.php">Referral Payouts</a>
                        <a href="<?= SITE_URL; ?>/admin/create-user.php">Create User</a>
                    </div>
                </div>

                <a href="<?= SITE_URL; ?>/admin/logout.php" class="nav-btn nav-btn-outline">লগআউট</a>
            </nav>

        </div>
    </div>
</header>

<style>
.admin-nav{
    display:flex;
    align-items:center;
    gap:18px;
    flex-wrap:wrap;
}

.admin-nav-dropdown{
    position:relative;
    display:inline-flex;
    align-items:center;
}

.admin-nav-dropdown-toggle{
    border:none;
    background:transparent;
    cursor:pointer;
    font:inherit;
    font-weight:600;
    color:inherit;
    padding:0;
}

.admin-nav-dropdown-menu{
    position:absolute;
    top:calc(100% + 12px);
    left:0;
    min-width:220px;
    background:#fff;
    border:1px solid #dbe4ee;
    border-radius:14px;
    box-shadow:0 14px 30px rgba(15, 23, 42, 0.10);
    padding:10px;
    display:none;
    z-index:999;
}

.admin-nav-dropdown-menu a{
    display:block;
    padding:10px 12px;
    border-radius:10px;
    text-decoration:none;
    color:#0f172a;
    font-size:14px;
    font-weight:600;
}

.admin-nav-dropdown-menu a:hover{
    background:#f8fbff;
}

.admin-nav-dropdown.is-open .admin-nav-dropdown-menu{
    display:block;
}

@media (max-width: 1024px){
    .admin-nav{
        gap:14px;
    }
}

@media (max-width: 860px){
    .admin-nav{
        position:absolute;
        top:calc(100% + 14px);
        right:0;
        left:0;
        display:none;
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
        background:#fff;
        border:1px solid #dbe4ee;
        border-radius:18px;
        padding:18px;
        box-shadow:0 18px 40px rgba(15, 23, 42, 0.10);
        z-index:999;
    }

    .admin-nav.is-open{
        display:flex;
    }

    .admin-nav-dropdown{
        width:100%;
        display:flex;
        flex-direction:column;
        align-items:flex-start;
    }

    .admin-nav-dropdown-toggle{
        width:100%;
        text-align:left;
    }

    .admin-nav-dropdown-menu{
        position:static;
        display:none;
        width:100%;
        min-width:unset;
        margin-top:10px;
        box-shadow:none;
        border-radius:12px;
    }

    .admin-nav-dropdown.is-open .admin-nav-dropdown-menu{
        display:block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('adminMenuToggle');
    const nav = document.getElementById('adminMainNav');
    const moreToggle = document.getElementById('adminMoreToggle');
    const moreDropdown = document.getElementById('adminMoreDropdown');

    if (menuToggle && nav) {
        menuToggle.addEventListener('click', function () {
            nav.classList.toggle('is-open');
        });
    }

    if (moreToggle && moreDropdown) {
        moreToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            moreDropdown.classList.toggle('is-open');
        });

        document.addEventListener('click', function (e) {
            if (!moreDropdown.contains(e.target)) {
                moreDropdown.classList.remove('is-open');
            }
        });
    }
});
</script>