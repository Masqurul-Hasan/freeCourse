<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

$userId = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

/* =========================================
   REFERRAL STATS
   ========================================= */
$myReferralCode = trim((string)($user['referral_code'] ?? ''));

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE referred_by = ?
");
$stmt->execute([$myReferralCode]);
$totalReferrals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE referred_by = ? AND kyc_status = 'approved'
");
$stmt->execute([$myReferralCode]);
$approvedReferrals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE referred_by = ? AND kyc_status = 'pending'
");
$stmt->execute([$myReferralCode]);
$pendingReferrals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM referral_payouts
    WHERE referrer_user_id = ?
");
$stmt->execute([$userId]);
$totalReferralEarnings = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM referral_payouts
    WHERE referrer_user_id = ? AND status = 'pending'
");
$stmt->execute([$userId]);
$pendingReferralPayout = (float)$stmt->fetchColumn();

$profileImage = trim((string)($user['profile_image'] ?? ''));
$profileImageUrl = $profileImage !== ''
    ? SITE_URL . '/' . ltrim($profileImage, '/')
    : null;

$page_title = 'আমার প্রোফাইল';
$meta_description = 'ব্যবহারকারীর প্রোফাইল';

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<style>
.profile-shell{
    padding:28px 0 48px;
}

.profile-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
}

.profile-card{
    background:#fff;
    border:1px solid #e7edf5;
    border-radius:24px;
    padding:24px;
    box-shadow:0 14px 35px rgba(15,23,42,.04);
}

.profile-hero{
    grid-column:1 / -1;
    background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
    border:1px solid #e7edf5;
    border-radius:28px;
    padding:28px;
    box-shadow:0 16px 40px rgba(15,23,42,.05);
    position:relative;
}

.profile-hero-center{
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
}

.profile-avatar-wrap{
    position:relative;
    width:118px;
    height:118px;
    margin-bottom:18px;
}

.profile-avatar{
    width:118px;
    height:118px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid #e8eef8;
    background:#f1f5f9;
    display:block;
}

.profile-avatar-fallback{
    width:118px;
    height:118px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:38px;
    font-weight:900;
    color:#1d4ed8;
    background:linear-gradient(135deg,#dbeafe 0%,#eff6ff 100%);
    border:4px solid #e8eef8;
}

.profile-edit-icon{
    position:absolute;
    top:18px;
    right:18px;
    width:42px;
    height:42px;
    border:none;
    border-radius:14px;
    background:#fff;
    border:1px solid #dbe4ee;
    box-shadow:0 8px 20px rgba(15,23,42,.06);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    font-size:18px;
    transition:.2s ease;
}

.profile-edit-icon:hover{
    background:#f8fbff;
}

.profile-image-upload{
    display:none;
    margin-top:12px;
    width:100%;
    max-width:320px;
}

.profile-image-upload input{
    width:100%;
}

.profile-eyebrow{
    margin:0 0 8px;
    font-size:13px;
    font-weight:800;
    letter-spacing:.14em;
    text-transform:uppercase;
    color:#2563eb;
}

.profile-title{
    margin:0;
    font-size:clamp(28px,3vw,40px);
    line-height:1.08;
    font-weight:900;
    color:#0f172a;
}

.profile-subtitle{
    margin:10px 0 0;
    color:#64748b;
    line-height:1.7;
    font-size:15px;
    max-width:720px;
}

.profile-top-meta{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    justify-content:center;
    margin-top:16px;
}

.profile-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:36px;
    padding:8px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:800;
}

.profile-pill.approved{
    background:rgba(22,163,74,.10);
    color:#15803d;
}

.profile-pill.pending{
    background:rgba(245,158,11,.12);
    color:#b45309;
}

.profile-pill.rejected{
    background:rgba(220,38,38,.10);
    color:#b91c1c;
}

.profile-card h2{
    margin:0 0 14px;
    font-size:24px;
    color:#0f172a;
    font-weight:900;
}

.profile-form{
    margin:0;
}

.profile-info-list{
    display:grid;
    gap:12px;
}

.profile-info-item{
    display:grid;
    grid-template-columns:160px 1fr;
    gap:16px;
    align-items:center;
    padding:12px 0;
    border-bottom:1px solid #eef2f7;
}

.profile-info-item:last-child{
    border-bottom:none;
    padding-bottom:0;
}

.profile-label{
    color:#64748b;
    font-weight:700;
}

.profile-static-value{
    color:#0f172a;
    font-weight:700;
    word-break:break-word;
}

.profile-inline-input{
    width:100%;
    min-height:46px;
    border:1px solid transparent;
    background:transparent;
    padding:10px 0;
    font-size:15px;
    color:#0f172a;
    font-weight:700;
    outline:none;
    pointer-events:none;
}

.profile-inline-input[readonly]{
    cursor:default;
}

.profile-form.is-editing .profile-inline-input{
    pointer-events:auto;
    background:#fff;
    border-color:#dbe4ee;
    border-radius:12px;
    padding:10px 14px;
    font-weight:600;
}

.profile-form.is-editing .profile-inline-input:focus{
    border-color:#93c5fd;
    box-shadow:0 0 0 4px rgba(59,130,246,.10);
}

.profile-actions{
    display:none;
    gap:12px;
    flex-wrap:wrap;
    margin-top:18px;
}

.profile-form.is-editing .profile-actions{
    display:flex;
}

.profile-form.is-editing .profile-image-upload{
    display:block;
}

.profile-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:48px;
    padding:0 18px;
    border:none;
    border-radius:14px;
    font-weight:800;
    text-decoration:none;
    cursor:pointer;
    transition:.25s ease;
}

.profile-btn-primary{
    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    color:#fff;
}

.profile-btn-primary:hover{
    color:#fff;
    transform:translateY(-1px);
}

.profile-btn-light{
    background:#fff;
    color:#0f172a;
    border:1px solid #dbe4ee;
}

.profile-btn-light:hover{
    background:#f8fbff;
    color:#0f172a;
}

.profile-ref-box{
    background:#f8fbff;
    border:1px dashed #cfe0ff;
    border-radius:16px;
    padding:16px;
    margin-bottom:16px;
}

.profile-ref-code{
    font-size:22px;
    font-weight:900;
    color:#1d4ed8;
    letter-spacing:.06em;
    margin-top:6px;
    word-break:break-word;
}

.profile-ref-link{
    width:100%;
    margin-top:10px;
    min-height:48px;
    border:1px solid #dbe4ee;
    border-radius:12px;
    padding:12px 14px;
    font-size:14px;
    color:#0f172a;
}

.profile-stats{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}

.profile-stat-box{
    background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
    border:1px solid #e7edf5;
    border-radius:18px;
    padding:18px;
}

.profile-stat-label{
    font-size:13px;
    font-weight:700;
    color:#64748b;
    margin-bottom:8px;
}

.profile-stat-value{
    font-size:28px;
    line-height:1.05;
    font-weight:900;
    color:#0f172a;
    margin-bottom:6px;
}

.profile-stat-note{
    color:#64748b;
    font-size:13px;
    line-height:1.6;
}

.form-group{
    margin-bottom:16px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    color:#0f172a;
    font-weight:800;
}

.form-group input{
    width:100%;
    min-height:50px;
    border:1px solid #dbe4ee;
    border-radius:14px;
    padding:12px 14px;
    font-size:14px;
    outline:none;
    background:#fff;
}

.form-group input:focus{
    border-color:#93c5fd;
    box-shadow:0 0 0 4px rgba(59,130,246,.10);
}

.alert{
    margin-bottom:18px;
}

@media (max-width: 900px){
    .profile-grid{
        grid-template-columns:1fr;
    }

    .profile-info-item{
        grid-template-columns:1fr;
        gap:8px;
    }

    .profile-stats{
        grid-template-columns:1fr;
    }
}

@media (max-width: 600px){
    .profile-shell{
        padding:20px 0 36px;
    }

    .profile-hero,
    .profile-card{
        padding:18px;
        border-radius:20px;
    }

    .profile-edit-icon{
        top:14px;
        right:14px;
        width:40px;
        height:40px;
    }

    .profile-actions{
        flex-direction:column;
    }

    .profile-btn{
        width:100%;
    }
}
</style>

<main class="profile-shell">
    <div class="container">

        <?php if ($msg = getFlash('success')): ?>
            <div class="alert alert-success"><?= e($msg); ?></div>
        <?php endif; ?>

        <?php if ($msg = getFlash('error')): ?>
            <div class="alert alert-error"><?= e($msg); ?></div>
        <?php endif; ?>

        <section class="profile-hero">
            <button type="button" class="profile-edit-icon" id="editProfileBtn" aria-label="Edit Profile">✎</button>

            <form
                action="<?= SITE_URL; ?>/actions/user/update-profile-action.php"
                method="POST"
                enctype="multipart/form-data"
                id="profileForm"
                class="profile-form"
            >
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                <div class="profile-hero-center">
                    <div class="profile-avatar-wrap">
                        <?php if ($profileImageUrl): ?>
                            <img src="<?= e($profileImageUrl); ?>" alt="Profile Image" class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-avatar-fallback">
                                <?= e(mb_strtoupper(mb_substr((string)$user['name'], 0, 1))); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-image-upload" id="profileImageUploadWrap">
                        <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <p class="profile-eyebrow">My Profile</p>
                    <h1 class="profile-title"><?= e($user['name']); ?></h1>
                    <p class="profile-subtitle">
                        আপনার account, profile information, referral code এবং security settings এখান থেকে manage করুন।
                    </p>

                    <div class="profile-top-meta">
                        <span class="profile-pill <?= $user['kyc_status'] === 'approved' ? 'approved' : ($user['kyc_status'] === 'rejected' ? 'rejected' : 'pending'); ?>">
                            KYC: <?= e(ucfirst((string)$user['kyc_status'])); ?>
                        </span>

                        <span class="profile-pill <?= $user['account_status'] === 'active' ? 'approved' : 'rejected'; ?>">
                            Account: <?= e(ucfirst((string)$user['account_status'])); ?>
                        </span>
                    </div>
                </div>

                <div class="profile-card" style="margin-top:22px; padding:0; border:none; box-shadow:none; background:transparent;">
                    <h2>Basic Information</h2>

                    <div class="profile-info-list">
                        <div class="profile-info-item">
                            <span class="profile-label">নাম</span>
                            <input
                                type="text"
                                name="name"
                                class="profile-inline-input"
                                value="<?= e($user['name']); ?>"
                                data-initial="<?= e($user['name']); ?>"
                                readonly
                                required
                            >
                        </div>

                        <div class="profile-info-item">
                            <span class="profile-label">ইউজার UID</span>
                            <span class="profile-static-value"><?= e($user['user_uid']); ?></span>
                        </div>

                        <div class="profile-info-item">
                            <span class="profile-label">মোবাইল</span>
                            <input
                                type="text"
                                name="phone"
                                class="profile-inline-input"
                                value="<?= e($user['phone']); ?>"
                                data-initial="<?= e($user['phone']); ?>"
                                readonly
                                required
                            >
                        </div>

                        <div class="profile-info-item">
                            <span class="profile-label">ইমেইল</span>
                            <input
                                type="email"
                                name="email"
                                class="profile-inline-input"
                                value="<?= e($user['email'] ?? ''); ?>"
                                data-initial="<?= e($user['email'] ?? ''); ?>"
                                readonly
                            >
                        </div>

                        <div class="profile-info-item">
                            <span class="profile-label">ওয়ালেট ব্যালেন্স</span>
                            <span class="profile-static-value">৳<?= number_format((float)$user['wallet_balance'], 2); ?></span>
                        </div>

                        <div class="profile-info-item">
                            <span class="profile-label">তৈরির তারিখ</span>
                            <span class="profile-static-value"><?= e($user['created_at'] ?? 'N/A'); ?></span>
                        </div>
                    </div>

                    <div class="profile-actions" id="profileActions">
                        <button type="submit" class="profile-btn profile-btn-primary">Save</button>
                        <button type="button" class="profile-btn profile-btn-light" id="cancelProfileEdit">Cancel</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="profile-grid">

            <div class="profile-card">
                <h2>Referral Program</h2>

                <div class="profile-ref-box">
                    <div class="profile-label">আপনার রেফারেল কোড</div>
                    <div class="profile-ref-code"><?= e($myReferralCode ?: 'N/A'); ?></div>

                    <div class="profile-label" style="margin-top:12px;">Referral Link</div>
                    <input
                        type="text"
                        class="profile-ref-link"
                        id="refLink"
                        value="<?= e(SITE_URL . '/register.php?ref=' . $myReferralCode); ?>"
                        readonly
                    >
                </div>

                <div class="form-actions" style="margin-bottom:16px;">
                    <button type="button" class="profile-btn profile-btn-primary" onclick="copyReferralLink()">Referral Link Copy করুন</button>
                </div>

                <div class="profile-stats">
                    <div class="profile-stat-box">
                        <div class="profile-stat-label">Total Referrals</div>
                        <div class="profile-stat-value"><?= $totalReferrals; ?></div>
                        <div class="profile-stat-note">মোট কতজন আপনার কোড ব্যবহার করেছে</div>
                    </div>

                    <div class="profile-stat-box">
                        <div class="profile-stat-label">Approved Referrals</div>
                        <div class="profile-stat-value"><?= $approvedReferrals; ?></div>
                        <div class="profile-stat-note">KYC approved referred users</div>
                    </div>

                    <div class="profile-stat-box">
                        <div class="profile-stat-label">Pending Referrals</div>
                        <div class="profile-stat-value"><?= $pendingReferrals; ?></div>
                        <div class="profile-stat-note">এখনো review pending</div>
                    </div>

                    <div class="profile-stat-box">
                        <div class="profile-stat-label">Referral Earnings</div>
                        <div class="profile-stat-value">৳<?= number_format($totalReferralEarnings, 2); ?></div>
                        <div class="profile-stat-note">Pending: ৳<?= number_format($pendingReferralPayout, 2); ?></div>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <h2>Change Password</h2>

                <form action="<?= SITE_URL; ?>/actions/user/change-password-action.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="profile-btn profile-btn-primary">Password Change করুন</button>
                    </div>
                </form>
            </div>

        </section>
    </div>
</main>

<script>
function copyReferralLink() {
    const input = document.getElementById('refLink');
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');
    alert('Referral link copied!');
}

(function () {
    const form = document.getElementById('profileForm');
    const editBtn = document.getElementById('editProfileBtn');
    const cancelBtn = document.getElementById('cancelProfileEdit');

    if (!form || !editBtn || !cancelBtn) return;

    const editableFields = form.querySelectorAll('.profile-inline-input');

    function enableEditMode() {
        form.classList.add('is-editing');
        editableFields.forEach(field => {
            field.removeAttribute('readonly');
        });
    }

    function disableEditMode(resetValues = false) {
        form.classList.remove('is-editing');
        editableFields.forEach(field => {
            if (resetValues) {
                field.value = field.getAttribute('data-initial') || '';
            }
            field.setAttribute('readonly', 'readonly');
        });

        const imageInput = form.querySelector('input[name="profile_image"]');
        if (imageInput) {
            imageInput.value = '';
        }
    }

    editBtn.addEventListener('click', function () {
        enableEditMode();
    });

    cancelBtn.addEventListener('click', function () {
        disableEditMode(true);
    });
})();
</script>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>