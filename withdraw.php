<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

/* =========================================================
   REFRESH USER DATA
   ========================================================= */
$stmt = $pdo->prepare("
    SELECT id, user_uid, name, phone, wallet_balance, kyc_status, account_status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_unset();
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

$_SESSION['user_uid'] = $user['user_uid'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_phone'] = $user['phone'];
$_SESSION['kyc_status'] = $user['kyc_status'];
$_SESSION['account_status'] = $user['account_status'];

$walletBalance = (float) $user['wallet_balance'];
$minimumWithdraw = 1000;
$canWithdraw = (
    $_SESSION['account_status'] === 'active' &&
    $_SESSION['kyc_status'] === 'approved' &&
    $walletBalance >= $minimumWithdraw
);

$page_title = 'উইথড্র';
$meta_description = 'ওয়ালেট ব্যালেন্স এবং উইথড্র রিকোয়েস্ট';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">

        <?php if ($msg = getFlash('success')): ?>
            <div class="alert alert-success"><?= e($msg); ?></div>
        <?php endif; ?>

        <?php if ($msg = getFlash('error')): ?>
            <div class="alert alert-error"><?= e($msg); ?></div>
        <?php endif; ?>

        <section class="withdraw-hero card">
            <div class="withdraw-hero-content">
                <div>
                    <p class="withdraw-eyebrow">Withdraw Center</p>
                    <h1>উইথড্র সেন্টার</h1>
                    <p class="withdraw-hero-text">
                        আপনার বর্তমান ব্যালেন্স, KYC অবস্থা এবং withdraw request এখান থেকে ম্যানেজ করুন।
                    </p>
                </div>

                <div class="withdraw-hero-balance">
                    <span class="withdraw-balance-label">Available Balance</span>
                    <strong>৳<?= number_format($walletBalance, 2); ?></strong>
                </div>
            </div>
        </section>

        <section class="withdraw-summary-grid">
            <div class="withdraw-summary-card card">
                <span class="withdraw-summary-label">বর্তমান ব্যালেন্স</span>
                <strong class="withdraw-summary-value">৳<?= number_format($walletBalance, 2); ?></strong>
                <small class="withdraw-summary-note">আপনার বর্তমান withdrawable balance</small>
            </div>

            <div class="withdraw-summary-card card">
                <span class="withdraw-summary-label">সর্বনিম্ন উইথড্র</span>
                <strong class="withdraw-summary-value">৳<?= number_format($minimumWithdraw, 2); ?></strong>
                <small class="withdraw-summary-note">এর নিচে withdraw request করা যাবে না</small>
            </div>

            <div class="withdraw-summary-card card">
                <span class="withdraw-summary-label">KYC স্ট্যাটাস</span>
                <strong class="withdraw-summary-value small-value"><?= e($_SESSION['kyc_status']); ?></strong>
                <small class="withdraw-summary-note">Withdraw করার জন্য approved হওয়া দরকার</small>
            </div>
        </section>

        <?php if ($_SESSION['account_status'] !== 'active'): ?>
            <section class="card withdraw-state-card">
                <h2>আপনার অ্যাকাউন্ট সক্রিয় নয়</h2>
                <p>বর্তমানে আপনার অ্যাকাউন্ট suspended রয়েছে, তাই withdraw request করা যাবে না।</p>
            </section>

        <?php elseif ($_SESSION['kyc_status'] !== 'approved'): ?>
            <section class="card withdraw-state-card">
                <h2>Withdraw করার আগে KYC অনুমোদন প্রয়োজন</h2>
                <p>আপনার KYC approved না হওয়া পর্যন্ত withdraw request করা যাবে না।</p>
            </section>

        <?php else: ?>

            <section class="withdraw-layout">
                <div class="card withdraw-form-card">
                    <div class="section-head">
                        <h2>নতুন Withdraw Request</h2>
                        <p>সঠিক তথ্য দিয়ে আপনার withdraw request সাবমিট করুন</p>
                    </div>

                    <form action="<?= SITE_URL; ?>/actions/user/create-withdraw-request.php" method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount">উইথড্র এমাউন্ট</label>
                                <input
                                    type="number"
                                    id="amount"
                                    name="amount"
                                    min="1000"
                                    step="0.01"
                                    placeholder="যেমন: 1000"
                                    required
                                >
                                <small>সর্বনিম্ন ৳<?= number_format($minimumWithdraw, 2); ?> withdraw করা যাবে</small>
                            </div>

                            <div class="form-group">
                                <label for="payment_method">পেমেন্ট মেথড</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="bkash">bKash</option>
                                    <option value="nagad">Nagad</option>
                                    <option value="rocket">Rocket</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="payment_number">পেমেন্ট নাম্বার</label>
                                <input
                                    type="text"
                                    id="payment_number"
                                    name="payment_number"
                                    placeholder="01XXXXXXXXX"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="note">নোট (ঐচ্ছিক)</label>
                                <input
                                    type="text"
                                    id="note"
                                    name="note"
                                    placeholder="অতিরিক্ত তথ্য লিখুন"
                                >
                            </div>
                        </div>

                        <?php if ($walletBalance < $minimumWithdraw): ?>
                            <div class="alert alert-warning">
                                আপনার ব্যালেন্স সর্বনিম্ন withdraw সীমার নিচে আছে। আরও আয় হলে withdraw করতে পারবেন।
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary auth-submit" <?= !$canWithdraw ? 'disabled' : ''; ?>>
                                Withdraw Request পাঠান
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="card withdraw-side-card">
                    <h3>Withdraw Rules</h3>

                    <div class="withdraw-rule-list">
                        <div class="withdraw-rule-item">
                            <strong>ন্যূনতম withdraw</strong>
                            <span>৳<?= number_format($minimumWithdraw, 2); ?></span>
                        </div>

                        <div class="withdraw-rule-item">
                            <strong>KYC প্রয়োজন</strong>
                            <span>Approved</span>
                        </div>

                        <div class="withdraw-rule-item">
                            <strong>অ্যাকাউন্ট স্ট্যাটাস</strong>
                            <span>Active</span>
                        </div>

                        <div class="withdraw-rule-item">
                            <strong>পেমেন্ট মেথড</strong>
                            <span>bKash / Nagad / Rocket</span>
                        </div>
                    </div>

                    <div class="withdraw-side-note">
                        Request জমা দেওয়ার পর এডমিন রিভিউ করবে। অনুমোদনের পর আপনার পেমেন্ট পাঠানো হবে।
                    </div>
                </aside>
            </section>

        <?php endif; ?>

    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>