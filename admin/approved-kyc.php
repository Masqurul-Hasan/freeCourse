<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'Approved KYC';
$meta_description = 'Approved KYC list';

$stmt = $pdo->query("
    SELECT
        u.id AS user_id,
        u.name,
        u.phone,
        u.email,
        u.user_uid,
        u.kyc_status,

        k.id AS kyc_id,
        k.nid_number,
        k.date_of_birth,
        k.bkash_number,
        k.nid_front_image,
        k.nid_back_image,
        k.status,
        k.submitted_at,
        k.reviewed_at

    FROM users u

    LEFT JOIN kyc_submissions k
        ON k.id = (
            SELECT ks.id
            FROM kyc_submissions ks
            WHERE ks.user_id = u.id
            ORDER BY ks.id DESC
            LIMIT 1
        )

    WHERE u.kyc_status = 'approved'

    ORDER BY u.id DESC
");

$kycRows = $stmt->fetchAll();

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
<div class="container">

<div class="card" style="padding:24px">

<div class="section-head">
<h1>Approved KYC তালিকা</h1>
<p>যেসব ইউজারের KYC approve হয়েছে</p>
</div>

<?php if (empty($kycRows)): ?>

<div class="empty-state">
কোনো approved KYC পাওয়া যায়নি।
</div>

<?php else: ?>

<div class="table-wrap">

<table class="table">

<thead>
<tr>
<th>#</th>
<th>ইউজার</th>
<th>মোবাইল</th>
<th>NID নাম্বার</th>
<th>বিকাশ</th>
<th>স্ট্যাটাস</th>
<th>Approve সময়</th>
<th>অ্যাকশন</th>
</tr>
</thead>

<tbody>

<?php foreach ($kycRows as $index => $row): ?>

<tr>

<td><?= $index+1 ?></td>

<td>
<strong><?= e($row['name']) ?></strong><br>
<small><?= e($row['user_uid']) ?></small>
</td>

<td><?= e($row['phone']) ?></td>

<td><?= e($row['nid_number']) ?></td>

<td><?= e($row['bkash_number']) ?></td>

<td>
<span class="status-badge status-success">
approved
</span>
</td>

<td><?= e($row['reviewed_at']) ?></td>

<td>
<a href="<?= SITE_URL ?>/admin/kyc-details.php?user_id=<?= (int)$row['user_id'] ?>" class="action-btn action-btn-view">
View
</a>
</td>

</tr>

<?php endforeach ?>

</tbody>

</table>

</div>

<?php endif ?>

</div>

</div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>