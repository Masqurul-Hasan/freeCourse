<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

function save_old_register_input(array $data): void
{
    $_SESSION['old_register'] = [
        'name' => $data['name'] ?? '',
        'phone' => $data['phone'] ?? '',
        'email' => $data['email'] ?? '',
        'referral_code' => $data['referral_code'] ?? '',
        'nid_number' => $data['nid_number'] ?? '',
        'date_of_birth' => $data['date_of_birth'] ?? '',
        'bkash_number' => $data['bkash_number'] ?? '',
    ];
}

function make_referral_code(int $length = 8): string
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $maxIndex = strlen($characters) - 1;
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $maxIndex)];
    }

    return $code;
}

function generate_unique_referral_code(PDO $pdo, int $length = 8): string
{
    do {
        $code = make_referral_code($length);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    } while ($exists);

    return $code;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/register.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/register.php?step=1');
}

/* =========================================================
   1. GET & SANITIZE INPUTS
   ========================================================= */
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$referral_input = trim($_POST['referral_code'] ?? '');

$nid_number = trim($_POST['nid_number'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$bkash_number = trim($_POST['bkash_number'] ?? '');

$oldInput = [
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'referral_code' => $referral_input,
    'nid_number' => $nid_number,
    'date_of_birth' => $date_of_birth,
    'bkash_number' => $bkash_number,
];

/* =========================================================
   2. BASIC INFO VALIDATION
   ========================================================= */
if ($name === '' || $phone === '' || $password === '' || $confirm_password === '') {
    save_old_register_input($oldInput);
    setFlash('error', 'প্রথম ধাপের সব প্রয়োজনীয় তথ্য পূরণ করুন।');
    redirect(SITE_URL . '/register.php?step=1');
}

if ($password !== $confirm_password) {
    save_old_register_input($oldInput);
    setFlash('error', 'পাসওয়ার্ড মিলছে না।');
    redirect(SITE_URL . '/register.php?step=1');
}

if (strlen($password) < 6) {
    save_old_register_input($oldInput);
    setFlash('error', 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।');
    redirect(SITE_URL . '/register.php?step=1');
}

/* =========================================================
   3. KYC VALIDATION
   ========================================================= */
if ($nid_number === '' || $date_of_birth === '' || $bkash_number === '') {
    save_old_register_input($oldInput);
    setFlash('error', 'KYC ধাপের সব প্রয়োজনীয় তথ্য পূরণ করুন।');
    redirect(SITE_URL . '/register.php?step=2');
}

if (
    empty($_FILES['nid_front_image']['name']) ||
    empty($_FILES['nid_back_image']['name'])
) {
    save_old_register_input($oldInput);
    setFlash('error', 'এনআইডি ফ্রন্ট এবং ব্যাক ছবি দিন।');
    redirect(SITE_URL . '/register.php?step=2');
}

/* =========================================================
   4. DUPLICATE CHECKS
   ========================================================= */
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
$stmt->execute([$phone]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    save_old_register_input($oldInput);
    setFlash('error', 'এই মোবাইল নাম্বার দিয়ে ইতোমধ্যে অ্যাকাউন্ট আছে।');
    redirect(SITE_URL . '/register.php?step=1');
}

if ($email !== '') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        save_old_register_input($oldInput);
        setFlash('error', 'এই ইমেইল দিয়ে ইতোমধ্যে অ্যাকাউন্ট আছে।');
        redirect(SITE_URL . '/register.php?step=1');
    }
}

$stmt = $pdo->prepare("SELECT id FROM kyc_submissions WHERE nid_number = ? LIMIT 1");
$stmt->execute([$nid_number]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    save_old_register_input($oldInput);
    setFlash('error', 'এই এনআইডি নাম্বার ইতোমধ্যে ব্যবহার করা হয়েছে।');
    redirect(SITE_URL . '/register.php?step=2');
}

$stmt = $pdo->prepare("SELECT id FROM kyc_submissions WHERE bkash_number = ? LIMIT 1");
$stmt->execute([$bkash_number]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    save_old_register_input($oldInput);
    setFlash('error', 'এই বিকাশ নাম্বার ইতোমধ্যে ব্যবহার করা হয়েছে।');
    redirect(SITE_URL . '/register.php?step=2');
}

/* =========================================================
   5. VALIDATE REFERRAL CODE
   Logic:
   - no referral => bonus 100
   - valid referral => bonus 150
   referred_by stores referral code string
   ========================================================= */
$referred_by = null;
$bonusAmount = 100;
$walletSource = 'signup_bonus';
$walletDescription = 'Signup bonus';
$referrerUserId = null;

if ($referral_input !== '') {
    $stmt = $pdo->prepare("
        SELECT id, referral_code
        FROM users
        WHERE referral_code = ?
        LIMIT 1
    ");
    $stmt->execute([$referral_input]);
    $refUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$refUser) {
        save_old_register_input($oldInput);
        setFlash('error', 'রেফারেল কোড সঠিক নয়।');
        redirect(SITE_URL . '/register.php?step=1');
    }

    $referred_by = $refUser['referral_code'];
    $referrerUserId = (int)$refUser['id'];
    $bonusAmount = 150;
    $walletSource = 'referral_bonus';
    $walletDescription = 'Referral signup bonus';
}

/* =========================================================
   6. GENERATE UNIQUE USER UID & REFERRAL CODE
   ========================================================= */
$user_uid = generateUserUID();
do {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE user_uid = ? LIMIT 1");
    $stmt->execute([$user_uid]);
    $exists_uid = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists_uid) {
        $user_uid = generateUserUID();
    }
} while ($exists_uid);

$my_referral_code = generate_unique_referral_code($pdo, 8);

/* =========================================================
   7. FILE VALIDATION
   ========================================================= */
$allowed_ext = ['jpg', 'jpeg', 'png'];
$max_size = 5 * 1024 * 1024;

$front = $_FILES['nid_front_image'];
$back = $_FILES['nid_back_image'];

$front_ext = strtolower(pathinfo($front['name'], PATHINFO_EXTENSION));
$back_ext = strtolower(pathinfo($back['name'], PATHINFO_EXTENSION));

if (!in_array($front_ext, $allowed_ext, true) || !in_array($back_ext, $allowed_ext, true)) {
    save_old_register_input($oldInput);
    setFlash('error', 'শুধুমাত্র JPG, JPEG অথবা PNG ফাইল গ্রহণযোগ্য।');
    redirect(SITE_URL . '/register.php?step=2');
}

if ($front['size'] > $max_size || $back['size'] > $max_size) {
    save_old_register_input($oldInput);
    setFlash('error', 'প্রতিটি ফাইল সর্বোচ্চ ৫ এমবি হতে হবে।');
    redirect(SITE_URL . '/register.php?step=2');
}

/* =========================================================
   8. INSERT USER + BONUS + KYC
   ========================================================= */
$plain_password = $password;

$frontAbsolutePath = null;
$backAbsolutePath = null;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO users (
            user_uid,
            name,
            phone,
            email,
            password,
            referral_code,
            referred_by,
            wallet_balance,
            kyc_status,
            account_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'active')
    ");

    $stmt->execute([
        $user_uid,
        $name,
        $phone,
        $email !== '' ? $email : null,
        $plain_password,
        $my_referral_code,
        $referred_by,
        0
    ]);

    $user_id = (int)$pdo->lastInsertId();

    /* wallet bonus */
    $stmt = $pdo->prepare("
        UPDATE users
        SET wallet_balance = wallet_balance + ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$bonusAmount, $user_id]);

    /* wallet transaction */
    $stmt = $pdo->prepare("
        INSERT INTO wallet_transactions (
            user_id,
            amount,
            type,
            source,
            reference_id,
            description,
            created_at
        ) VALUES (?, ?, 'credit', ?, 0, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $bonusAmount,
        $walletSource,
        $walletDescription
    ]);

    /* notification */
    $stmt = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            title,
            message,
            is_read,
            created_at
        ) VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([
        $user_id,
        $referrerUserId ? 'Referral Bonus Added' : 'Signup Bonus Added',
        $referrerUserId
            ? 'রেফারেল কোড ব্যবহার করে রেজিস্ট্রেশন করার জন্য আপনার wallet-এ ৳150.00 যোগ করা হয়েছে।'
            : 'রেজিস্ট্রেশন বোনাস হিসেবে আপনার wallet-এ ৳100.00 যোগ করা হয়েছে।'
    ]);

    /* =====================================================
       9. CREATE USER FOLDER
       ===================================================== */
    $projectRoot = realpath(__DIR__ . '/../../');

    if ($projectRoot === false) {
        throw new Exception('প্রজেক্ট root path পাওয়া যায়নি।');
    }

    $baseUploadAbsolute = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'nid' . DIRECTORY_SEPARATOR;

    if (!is_dir($baseUploadAbsolute)) {
        if (!mkdir($baseUploadAbsolute, 0777, true) && !is_dir($baseUploadAbsolute)) {
            throw new Exception('মূল uploads/nid ফোল্ডার তৈরি করা যায়নি।');
        }
    }

    if (!is_writable($baseUploadAbsolute)) {
        throw new Exception('uploads/nid ফোল্ডারে write permission নেই।');
    }

    $userFolderRelative = 'uploads/nid/' . $user_id . '/';
    $userFolderAbsolute = $baseUploadAbsolute . $user_id . DIRECTORY_SEPARATOR;

    if (!is_dir($userFolderAbsolute)) {
        if (!mkdir($userFolderAbsolute, 0777, true) && !is_dir($userFolderAbsolute)) {
            throw new Exception('ইউজার ফোল্ডার তৈরি করা যায়নি।');
        }
    }

    /* =====================================================
       10. SAVE FILES
       ===================================================== */
    $timestamp = time();
    $randomPart = bin2hex(random_bytes(4));

    $frontFileName = 'front_' . $timestamp . '_' . $randomPart . '.' . $front_ext;
    $backFileName  = 'back_' . $timestamp . '_' . $randomPart . '.' . $back_ext;

    $frontAbsolutePath = $userFolderAbsolute . $frontFileName;
    $backAbsolutePath  = $userFolderAbsolute . $backFileName;

    $frontDbPath = $userFolderRelative . $frontFileName;
    $backDbPath  = $userFolderRelative . $backFileName;

    if (!move_uploaded_file($front['tmp_name'], $frontAbsolutePath)) {
        throw new Exception('এনআইডি ফ্রন্ট ছবি আপলোড ব্যর্থ হয়েছে।');
    }

    if (!move_uploaded_file($back['tmp_name'], $backAbsolutePath)) {
        throw new Exception('এনআইডি ব্যাক ছবি আপলোড ব্যর্থ হয়েছে।');
    }

    /* =====================================================
       11. INSERT KYC SUBMISSION
       ===================================================== */
    $stmt = $pdo->prepare("
        INSERT INTO kyc_submissions (
            user_id,
            nid_number,
            date_of_birth,
            bkash_number,
            nid_front_image,
            nid_back_image,
            status,
            submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $user_id,
        $nid_number,
        $date_of_birth,
        $bkash_number,
        $frontDbPath,
        $backDbPath
    ]);

    $pdo->commit();

    unset($_SESSION['old_register']);

    setFlash('success', 'রেজিস্ট্রেশন এবং KYC জমা সম্পন্ন হয়েছে। এখন লগইন করুন।');
    redirect(SITE_URL . '/login.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (!empty($frontAbsolutePath) && file_exists($frontAbsolutePath)) {
        @unlink($frontAbsolutePath);
    }

    if (!empty($backAbsolutePath) && file_exists($backAbsolutePath)) {
        @unlink($backAbsolutePath);
    }

    save_old_register_input($oldInput);
    setFlash('error', $e->getMessage() ?: 'রেজিস্ট্রেশন সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/register.php?step=2');
}