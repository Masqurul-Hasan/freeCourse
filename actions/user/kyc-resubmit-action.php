<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/dashboard.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/dashboard.php');
}

$user_id = (int) $_SESSION['user_id'];
$kyc_id = (int) ($_POST['kyc_id'] ?? 0);
$nid_number = trim($_POST['nid_number'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$bkash_number = trim($_POST['bkash_number'] ?? '');

if ($kyc_id <= 0 || $nid_number === '' || $date_of_birth === '' || $bkash_number === '') {
    setFlash('error', 'সব প্রয়োজনীয় তথ্য পূরণ করুন।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM kyc_submissions
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$kyc_id, $user_id]);
$kyc = $stmt->fetch();

if (!$kyc) {
    setFlash('error', 'KYC তথ্য পাওয়া যায়নি।');
    redirect(SITE_URL . '/dashboard.php');
}

$allowed_ext = ['jpg', 'jpeg', 'png'];
$max_size = 5 * 1024 * 1024;

if (
    empty($_FILES['nid_front_image']['name']) ||
    empty($_FILES['nid_back_image']['name'])
) {
    setFlash('error', 'এনআইডি ফ্রন্ট এবং ব্যাক ছবি দিন।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

$front = $_FILES['nid_front_image'];
$back = $_FILES['nid_back_image'];

$front_ext = strtolower(pathinfo($front['name'], PATHINFO_EXTENSION));
$back_ext = strtolower(pathinfo($back['name'], PATHINFO_EXTENSION));

if (!in_array($front_ext, $allowed_ext) || !in_array($back_ext, $allowed_ext)) {
    setFlash('error', 'শুধুমাত্র JPG, JPEG অথবা PNG ফাইল গ্রহণযোগ্য।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

if ($front['size'] > $max_size || $back['size'] > $max_size) {
    setFlash('error', 'প্রতিটি ফাইল সর্বোচ্চ ৫ এমবি হতে হবে।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

// Duplicate check excluding current user's own KYC row
$stmt = $pdo->prepare("
    SELECT id
    FROM kyc_submissions
    WHERE nid_number = ? AND user_id != ?
    LIMIT 1
");
$stmt->execute([$nid_number, $user_id]);
if ($stmt->fetch()) {
    setFlash('error', 'এই এনআইডি নাম্বার ইতোমধ্যে ব্যবহার করা হয়েছে।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

$stmt = $pdo->prepare("
    SELECT id
    FROM kyc_submissions
    WHERE bkash_number = ? AND user_id != ?
    LIMIT 1
");
$stmt->execute([$bkash_number, $user_id]);
if ($stmt->fetch()) {
    setFlash('error', 'এই বিকাশ নাম্বার ইতোমধ্যে ব্যবহার করা হয়েছে।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

$projectRoot = realpath(__DIR__ . '/../../');

if ($projectRoot === false) {
    setFlash('error', 'প্রজেক্ট root path পাওয়া যায়নি।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

$baseUploadAbsolute = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'nid' . DIRECTORY_SEPARATOR;
$userFolderRelative = 'uploads/nid/' . $user_id . '/';
$userFolderAbsolute = $baseUploadAbsolute . $user_id . DIRECTORY_SEPARATOR;

if (!is_dir($userFolderAbsolute)) {
    if (!mkdir($userFolderAbsolute, 0777, true) && !is_dir($userFolderAbsolute)) {
        setFlash('error', 'ইউজার ফোল্ডার তৈরি করা যায়নি।');
        redirect(SITE_URL . '/resubmit-kyc.php');
    }
}

$timestamp = time();
$randomPart = bin2hex(random_bytes(4));

$frontFileName = 'front_' . $timestamp . '_' . $randomPart . '.' . $front_ext;
$backFileName  = 'back_' . $timestamp . '_' . $randomPart . '.' . $back_ext;

$frontAbsolutePath = $userFolderAbsolute . $frontFileName;
$backAbsolutePath  = $userFolderAbsolute . $backFileName;

$frontDbPath = $userFolderRelative . $frontFileName;
$backDbPath  = $userFolderRelative . $backFileName;

if (!move_uploaded_file($front['tmp_name'], $frontAbsolutePath)) {
    setFlash('error', 'এনআইডি ফ্রন্ট ছবি আপলোড ব্যর্থ হয়েছে।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

if (!move_uploaded_file($back['tmp_name'], $backAbsolutePath)) {
    setFlash('error', 'এনআইডি ব্যাক ছবি আপলোড ব্যর্থ হয়েছে।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE kyc_submissions
        SET
            nid_number = ?,
            date_of_birth = ?,
            bkash_number = ?,
            nid_front_image = ?,
            nid_back_image = ?,
            status = 'pending',
            admin_comment = NULL,
            submitted_at = NOW(),
            updated_at = NOW(),
            reviewed_at = NULL
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([
        $nid_number,
        $date_of_birth,
        $bkash_number,
        $frontDbPath,
        $backDbPath,
        $kyc_id,
        $user_id
    ]);

    $stmt = $pdo->prepare("
        UPDATE users
        SET kyc_status = 'pending'
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, is_read, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([
        $user_id,
        'KYC পুনরায় জমা হয়েছে',
        'আপনার KYC পুনরায় জমা হয়েছে। এডমিন রিভিউ করার পর আপনাকে জানানো হবে।'
    ]);

    $pdo->commit();

    $_SESSION['kyc_status'] = 'pending';

    setFlash('success', 'আপনার KYC পুনরায় জমা হয়েছে। এডমিন অনুমোদনের জন্য অপেক্ষা করুন।');
    redirect(SITE_URL . '/dashboard.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (file_exists($frontAbsolutePath)) {
        @unlink($frontAbsolutePath);
    }

    if (file_exists($backAbsolutePath)) {
        @unlink($backAbsolutePath);
    }

    setFlash('error', 'KYC পুনরায় জমা করা যায়নি। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/resubmit-kyc.php');
}