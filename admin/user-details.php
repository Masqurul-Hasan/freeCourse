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

$stmt = $pdo->prepare("
    SELECT *
    FROM kyc_submissions
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$kyc = $stmt->fetch();

$page_title = 'ইউজার ডিটেইলস';
$meta_description = 'ইউজারের বিস্তারিত তথ্য';

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card admin-panel-card">
            <div class="section-head">
                <h1>ইউজার ডিটেইলস</h1>
                <p>এই ইউজারের সম্পূর্ণ তথ্য এখানে দেখানো হচ্ছে</p>
            </div>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <div class="review-grid">
                <div class="card info-card">
                    <h3>বেসিক তথ্য</h3>
                    <div class="review-list">
                        <div><strong>নাম:</strong> <?= e($user['name']); ?></div>
                        <div><strong>ইউজার UID:</strong> <?= e($user['user_uid']); ?></div>
                        <div><strong>মোবাইল:</strong> <?= e($user['phone']); ?></div>
                        <div><strong>ইমেইল:</strong> <?= e($user['email'] ?: 'N/A'); ?></div>
                        <div><strong>পাসওয়ার্ড:</strong> <code><?= e($user['password']); ?></code></div>
                        <div><strong>রেফারেল কোড:</strong> <?= e($user['referral_code'] ?: 'N/A'); ?></div>
                        <div><strong>Referred By:</strong> <?= e($user['referred_by'] ?: 'N/A'); ?></div>
                        <div><strong>ওয়ালেট ব্যালেন্স:</strong> ৳<?= number_format((float)$user['wallet_balance'], 2); ?></div>
                        <div><strong>KYC স্ট্যাটাস:</strong> <?= e($user['kyc_status']); ?></div>
                        <div><strong>অ্যাকাউন্ট স্ট্যাটাস:</strong> <?= e($user['account_status']); ?></div>
                        <div><strong>তৈরির তারিখ:</strong> <?= e($user['created_at']); ?></div>
                    </div>
                </div>

                <div class="card info-card">
                    <h3>KYC তথ্য</h3>

                    <?php if ($kyc): ?>
                        <div class="review-list">
                            <div><strong>NID নাম্বার:</strong> <?= e($kyc['nid_number']); ?></div>
                            <div><strong>জন্ম তারিখ:</strong> <?= e($kyc['date_of_birth']); ?></div>
                            <div><strong>বিকাশ নাম্বার:</strong> <?= e($kyc['bkash_number']); ?></div>
                            <div><strong>KYC স্ট্যাটাস:</strong> <?= e($kyc['status']); ?></div>
                            <div><strong>এডমিন মন্তব্য:</strong> <?= e($kyc['admin_comment'] ?: 'N/A'); ?></div>
                            <div><strong>জমা সময়:</strong> <?= e($kyc['submitted_at']); ?></div>
                        </div>

                        <div class="form-actions" style="margin-top:18px;">
                            <a href="<?= SITE_URL; ?>/admin/kyc-details.php?id=<?= (int)$kyc['id']; ?>" class="btn-primary">KYC Review</a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">এই ইউজারের কোনো KYC তথ্য পাওয়া যায়নি।</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Wallet Control Section Start -->
            <div class="card info-card" style="margin-top: 24px;">
                <h3>ওয়ালেট ব্যালেন্স কন্ট্রোল</h3>
                <p style="margin-bottom: 16px; color: #666;">
                    এখান থেকে admin manual ভাবে wallet balance add বা deduct করতে পারবে।
                </p>

                <div class="review-list" style="margin-bottom: 18px;">
                    <div>
                        <strong>Current Wallet Balance:</strong>
                        ৳<?= number_format((float)$user['wallet_balance'], 2); ?>
                    </div>
                </div>

                <form action="<?= SITE_URL; ?>/actions/admin/wallet-adjustment-action.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="adjustment_type">Adjustment Type</label>
                            <select name="adjustment_type" id="adjustment_type" required>
                                <option value="">Select Type</option>
                                <option value="credit">Add Balance</option>
                                <option value="debit">Deduct Balance</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="amount"
                                id="amount"
                                placeholder="Enter amount"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason">Reason</label>
                        <textarea
                            name="reason"
                            id="reason"
                            rows="4"
                            placeholder="Example: Test balance, bonus, manual correction"
                            required
                        ></textarea>
                    </div>

                    <div class="form-actions" style="margin-top: 16px;">
                        <button
                            type="submit"
                            class="btn-primary"
                            onclick="return confirm('আপনি কি এই wallet adjustment করতে চান?');"
                        >
                            Wallet Adjust করুন
                        </button>
                    </div>
                </form>
            </div>
            <!-- Wallet Control Section End -->

            <div class="form-actions" style="margin-top: 24px;">
                <a href="<?= SITE_URL; ?>/admin/edit-user.php?id=<?= (int)$user['id']; ?>" class="btn-primary">ইউজার Edit করুন</a>
                <a href="<?= SITE_URL; ?>/admin/users.php" class="btn-light">সব ইউজারে ফিরে যান</a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>