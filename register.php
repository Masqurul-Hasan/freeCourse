<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$page_title = 'রেজিস্টার';
$meta_description = 'নতুন অ্যাকাউন্ট তৈরি করুন';

$ref_code = $_GET['ref'] ?? '';
$current_step = $_GET['step'] ?? '1';
$current_step = ($current_step === '2') ? '2' : '1';

$old = $_SESSION['old_register'] ?? [];
unset($_SESSION['old_register']);

function old_value($key, $default = '')
{
    global $old;
    return isset($old[$key]) ? $old[$key] : $default;
}

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <div class="card auth-card auth-card-wide">
            <div class="auth-head">
                <h1>নতুন অ্যাকাউন্ট তৈরি করুন</h1>
                <p>প্রথমে আপনার বেসিক তথ্য দিন, তারপর এনআইডি যাচাইকরণ সম্পন্ন করুন</p>
            </div>

            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-error"><?= e($msg); ?></div>
            <?php endif; ?>

            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success"><?= e($msg); ?></div>
            <?php endif; ?>

            <div class="step-indicator">
                <div class="step-item <?= $current_step === '1' ? 'is-active' : ''; ?>" id="stepIndicator1">
                    <span class="step-number">1</span>
                    <span class="step-label">বেসিক তথ্য</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item <?= $current_step === '2' ? 'is-active' : ''; ?>" id="stepIndicator2">
                    <span class="step-number">2</span>
                    <span class="step-label">KYC যাচাইকরণ</span>
                </div>
            </div>

            <form action="<?= SITE_URL; ?>/actions/auth/register-action.php" method="POST" enctype="multipart/form-data" class="auth-form" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                <!-- STEP 1 -->
                <div class="form-step <?= $current_step === '1' ? 'is-active' : ''; ?>" id="step1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">পূর্ণ নাম</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="আপনার পুরো নাম লিখুন"
                                autocomplete="name"
                                value="<?= e(old_value('name')); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">মোবাইল নাম্বার</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="01XXXXXXXXX"
                                autocomplete="tel"
                                inputmode="numeric"
                                value="<?= e(old_value('phone')); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">ইমেইল (ঐচ্ছিক)</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="example@email.com"
                                autocomplete="email"
                                value="<?= e(old_value('email')); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="referral_code">রেফারেল কোড (ঐচ্ছিক)</label>
                            <input
                                type="text"
                                id="referral_code"
                                name="referral_code"
                                placeholder="থাকলে লিখুন (ঐচ্ছিক)"
                                autocomplete="off"
                                value="<?= e(old_value('referral_code', $ref_code)); ?>"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">পাসওয়ার্ড</label>
                            <div class="password-wrap">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="কমপক্ষে ৬ অক্ষর"
                                    autocomplete="new-password"
                                    required
                                >
                                <button type="button" class="password-toggle" data-target="password" aria-label="পাসওয়ার্ড দেখুন">👁</button>
                            </div>
                            <small id="passwordStrengthText" class="help-text">কমপক্ষে ৬ অক্ষর ব্যবহার করুন</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">কনফার্ম পাসওয়ার্ড</label>
                            <div class="password-wrap">
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="আবার পাসওয়ার্ড লিখুন"
                                    autocomplete="new-password"
                                    required
                                >
                                <button type="button" class="password-toggle" data-target="confirm_password" aria-label="পাসওয়ার্ড দেখুন">👁</button>
                                <span class="match-indicator" id="matchIndicator">✓</span>
                            </div>
                            <small id="passwordMatchText" class="help-text">দুইটি পাসওয়ার্ড একই হতে হবে</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-primary auth-submit" id="nextStepBtn">পরবর্তী ধাপ</button>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div class="form-step <?= $current_step === '2' ? 'is-active' : ''; ?>" id="step2">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nid_number">এনআইডি নাম্বার</label>
                            <input
                                type="text"
                                id="nid_number"
                                name="nid_number"
                                placeholder="আপনার জাতীয় পরিচয়পত্র নম্বর"
                                autocomplete="off"
                                value="<?= e(old_value('nid_number')); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">জন্ম তারিখ</label>
                            <input
                                type="date"
                                id="date_of_birth"
                                name="date_of_birth"
                                autocomplete="bday"
                                value="<?= e(old_value('date_of_birth')); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bkash_number">বিকাশ নাম্বার</label>
                            <input
                                type="tel"
                                id="bkash_number"
                                name="bkash_number"
                                placeholder="01XXXXXXXXX"
                                autocomplete="tel"
                                inputmode="numeric"
                                value="<?= e(old_value('bkash_number')); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nid_front_image">এনআইডি ফ্রন্ট ছবি</label>
                        <input type="file" id="nid_front_image" name="nid_front_image" accept=".jpg,.jpeg,.png" required>
                        <small>সর্বোচ্চ ৫MB • JPG / JPEG / PNG • পরিষ্কার ছবি দিন</small>
                    </div>

                    <div class="form-group">
                        <label for="nid_back_image">এনআইডি ব্যাক ছবি</label>
                        <input type="file" id="nid_back_image" name="nid_back_image" accept=".jpg,.jpeg,.png" required>
                        <small>সর্বোচ্চ ৫MB • JPG / JPEG / PNG • পরিষ্কার ছবি দিন</small>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-light" id="prevStepBtn">পেছনে যান</button>
                        <button type="submit" class="btn-primary auth-submit">রেজিস্ট্রেশন সম্পন্ন করুন</button>
                    </div>
                </div>
            </form>

            <div class="auth-foot">
                <p>আগে থেকেই অ্যাকাউন্ট আছে? <a href="<?= SITE_URL; ?>/login.php">লগইন করুন</a></p>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const nextBtn = document.getElementById('nextStepBtn');
    const prevBtn = document.getElementById('prevStepBtn');
    const indicator1 = document.getElementById('stepIndicator1');
    const indicator2 = document.getElementById('stepIndicator2');

    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const matchIndicator = document.getElementById('matchIndicator');
    const matchText = document.getElementById('passwordMatchText');
    const strengthText = document.getElementById('passwordStrengthText');

    const requiredStep1Fields = ['name', 'phone', 'password', 'confirm_password'];

    function showStep(stepNumber) {
        if (stepNumber === 1) {
            step1.classList.add('is-active');
            step2.classList.remove('is-active');
            indicator1.classList.add('is-active');
            indicator2.classList.remove('is-active');
        } else {
            step1.classList.remove('is-active');
            step2.classList.add('is-active');
            indicator1.classList.remove('is-active');
            indicator2.classList.add('is-active');
        }
    }

    function updatePasswordStrength() {
        const value = password.value.trim();

        if (value.length === 0) {
            strengthText.textContent = 'কমপক্ষে ৬ অক্ষর ব্যবহার করুন';
            strengthText.className = 'help-text';
            return;
        }

        if (value.length < 6) {
            strengthText.textContent = 'পাসওয়ার্ড খুব ছোট';
            strengthText.className = 'help-text text-danger';
            return;
        }

        if (value.length >= 6 && value.length < 10) {
            strengthText.textContent = 'পাসওয়ার্ড গ্রহণযোগ্য';
            strengthText.className = 'help-text text-warning';
            return;
        }

        strengthText.textContent = 'পাসওয়ার্ড ভালো';
        strengthText.className = 'help-text text-success';
    }

    function updatePasswordMatch() {
        const pass = password.value;
        const confirm = confirmPassword.value;

        if (!confirm) {
            matchIndicator.classList.remove('is-visible', 'is-match', 'is-mismatch');
            matchText.textContent = 'দুইটি পাসওয়ার্ড একই হতে হবে';
            matchText.className = 'help-text';
            return;
        }

        matchIndicator.classList.add('is-visible');

        if (pass === confirm) {
            matchIndicator.classList.add('is-match');
            matchIndicator.classList.remove('is-mismatch');
            matchText.textContent = 'পাসওয়ার্ড মিলেছে';
            matchText.className = 'help-text text-success';
        } else {
            matchIndicator.classList.add('is-mismatch');
            matchIndicator.classList.remove('is-match');
            matchText.textContent = 'পাসওয়ার্ড মিলছে না';
            matchText.className = 'help-text text-danger';
        }
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            let valid = true;
            let firstInvalidField = null;

            requiredStep1Fields.forEach(function (fieldId) {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    valid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                }
            });

            if (!valid) {
                alert('প্রথম ধাপের সব প্রয়োজনীয় তথ্য পূরণ করুন।');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                return;
            }

            if (password.value.length < 6) {
                alert('পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।');
                password.focus();
                return;
            }

            if (password.value !== confirmPassword.value) {
                alert('পাসওয়ার্ড মিলছে না।');
                confirmPassword.focus();
                return;
            }

            showStep(2);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            showStep(1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁';
            }
        });
    });

    password.addEventListener('input', function () {
        updatePasswordStrength();
        updatePasswordMatch();
    });

    confirmPassword.addEventListener('input', updatePasswordMatch);

    updatePasswordStrength();
    updatePasswordMatch();
});
</script>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>