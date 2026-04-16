<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($user_id <= 0) {
    setFlash('error', 'অবৈধ ইউজার আইডি।');
    redirect(SITE_URL . '/admin/users.php');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'ইউজার পাওয়া যায়নি।');
    redirect(SITE_URL . '/admin/users.php');
}

$page_title = 'ইউজার এডিট';
$meta_description = 'ইউজার তথ্য এডিট করুন';

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card auth-card auth-card-wide">
            <div class="auth-head">
                <h1>ইউজার এডিট করুন</h1>
                <p>ইউজারের তথ্য, পাসওয়ার্ড, KYC স্ট্যাটাস এবং অ্যাকাউন্ট স্ট্যাটাস পরিবর্তন করুন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <form action="<?= SITE_URL; ?>/actions/admin/update-user-action.php" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">নাম</label>
                        <input type="text" id="name" name="name" value="<?= e($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">মোবাইল</label>
                        <input type="text" id="phone" name="phone" value="<?= e($user['phone']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">ইমেইল</label>
                        <input type="email" id="email" name="email" value="<?= e($user['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">পাসওয়ার্ড</label>
                        <input type="text" id="password" name="password" value="<?= e($user['password']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="kyc_status">KYC স্ট্যাটাস</label>
                        <select id="kyc_status" name="kyc_status" required>
                            <option value="pending" <?= $user['kyc_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?= $user['kyc_status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?= $user['kyc_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="resubmit_required" <?= $user['kyc_status'] === 'resubmit_required' ? 'selected' : ''; ?>>Resubmit Required</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="account_status">অ্যাকাউন্ট স্ট্যাটাস</label>
                        <select id="account_status" name="account_status" required>
                            <option value="active" <?= $user['account_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?= $user['account_status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">আপডেট করুন</button>
                    <a href="<?= SITE_URL; ?>/admin/user-details.php?id=<?= (int)$user['id']; ?>" class="btn-light">বাতিল</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>