<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'নতুন ইউজার তৈরি করুন';
$meta_description = 'এডমিন থেকে নতুন ইউজার তৈরি করুন';

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card auth-card auth-card-wide">
            <div class="auth-head">
                <h1>নতুন ইউজার তৈরি করুন</h1>
                <p>এডমিন প্যানেল থেকে সরাসরি নতুন ইউজার তৈরি করুন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <form action="<?= SITE_URL; ?>/actions/admin/create-user-action.php" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">নাম</label>
                        <input type="text" id="name" name="name" placeholder="ইউজারের নাম" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">মোবাইল</label>
                        <input type="text" id="phone" name="phone" placeholder="01XXXXXXXXX" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">ইমেইল</label>
                        <input type="email" id="email" name="email" placeholder="example@email.com">
                    </div>

                    <div class="form-group">
                        <label for="password">পাসওয়ার্ড</label>
                        <input type="text" id="password" name="password" placeholder="পাসওয়ার্ড দিন" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="wallet_balance">ওয়ালেট ব্যালেন্স</label>
                        <input type="number" step="0.01" id="wallet_balance" name="wallet_balance" value="0">
                    </div>

                    <div class="form-group">
                        <label for="referral_code">রেফারেল কোড</label>
                        <input type="text" id="referral_code" name="referral_code" placeholder="ফাঁকা রাখলে auto generate হবে">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="referred_by">Referred By</label>
                        <input type="text" id="referred_by" name="referred_by" placeholder="অন্য ইউজারের referral code">
                    </div>

                    <div class="form-group">
                        <label for="kyc_status">KYC স্ট্যাটাস</label>
                        <select id="kyc_status" name="kyc_status" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="resubmit_required">Resubmit Required</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="account_status">অ্যাকাউন্ট স্ট্যাটাস</label>
                        <select id="account_status" name="account_status" required>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">ইউজার তৈরি করুন</button>
                    <a href="<?= SITE_URL; ?>/admin/users.php" class="btn-light">বাতিল</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>