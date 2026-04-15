<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'হোম';
$meta_description = 'বাংলা মাইক্রোজব প্ল্যাটফর্মের হোমপেজ';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card" style="padding: 24px;">
            <h1>হোমপেজ শুরু</h1>
            <p>এখন layout system ready.</p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>