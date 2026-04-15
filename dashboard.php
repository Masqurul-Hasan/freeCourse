<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

$page_title = 'ড্যাশবোর্ড';
$meta_description = 'ইউজার ড্যাশবোর্ড';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card" style="padding:24px;">
            <h1>স্বাগতম, <?= e($_SESSION['user_name']); ?></h1>
            <p>আপনার ইউজার আইডি: <?= e($_SESSION['user_uid']); ?></p>
            <p>KYC স্ট্যাটাস: <?= e($_SESSION['kyc_status']); ?></p>
            <p>অ্যাকাউন্ট স্ট্যাটাস: <?= e($_SESSION['account_status']); ?></p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>