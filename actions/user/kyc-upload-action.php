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
    redirect(SITE_URL . '/submit-kyc.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/submit-kyc.php');
}

$user_id = $_SESSION['user_id'];
$nid_number = trim($_POST['nid_number'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$bkash_number = trim($_POST['bkash_number'] ?? '');

if ($nid_number === '' || $date_of_birth === '' || $bkash_number === '') {
    setFlash('error', 'সব তথ্য পূরণ করুন।');
    redirect(SITE_URL . '/submit-kyc.php');
}

// duplicate NID number check across other users
$stmt = $pdo->prepare("SELECT id FROM kyc_submissions WHERE nid_number = ? AND user_id != ? LIMIT 1");
$stmt->execute([$nid_number, $user_id]);
if ($stmt->fetch()) {
    setFlash('error', 'এই এনআইডি নাম্বার ইতোমধ্যে ব্যবহার করা হয়েছে।');
    redirect(SITE_URL . '/submit-kyc.php');
}

// duplicate bKash check across other users
$stmt = $pdo->prepare("SELECT id FROM kyc_submissions WHERE bkash_number = ? AND user_id != ? LIMIT 1");
$stmt->execute([$bkash_number, $user_id]);
if ($stmt->fetch()) {
    setFlash('error', 'এই বিকাশ নাম্বার ইতোমধ্যে ব্যবহার করা হয়েছে।');
    redirect(SITE_URL . '/submit-kyc.php');
}

if (
    empty($_FILES['nid_front_image']['name']) ||
    empty($_FILES['nid_back_image']['name'])
) {
    setFlash('error', 'এনআইডি ফ্রন্ট এবং ব্যাক ছবি দিন।');
    redirect(SITE_URL . '/submit-kyc.php');
}

$allowed_ext = ['jpg', 'jpeg', 'png'];
$max_size = 5 * 1024 * 1024;

$front = $_FILES['nid_front_image'];
$back = $_FILES['nid_back_image'];

$front_ext = strtolower(pathinfo($front['name'], PATHINFO_EXTENSION));
$back_ext = strtolower(pathinfo($back['name'], PATHINFO_EXTENSION));

if (!in_array($front_ext, $allowed_ext) || !in_array($back_ext, $allowed_ext)) {
    setFlash('error', 'শুধুমাত্র JPG, JPEG অথবা PNG ফাইল গ্রহণযোগ্য।');
    redirect(SITE_URL . '/submit-kyc.php');
}

if ($front['size'] > $max_size || $back['size'] > $max_size) {
    setFlash('error', 'প্রতিটি ফাইল সর্বোচ্চ ৫ এমবি হতে হবে।');
    redirect(SITE_URL . '/submit-kyc.php');
}

$front_name = 'front_' . $user_id . '_' . time() . '.' . $front_ext;
$back_name = 'back_' . $user_id . '_' . time() . '.' . $back_ext;

$front_path = __DIR__ . '/../../uploads/nid/front/' . $front_name;
$back_path = __DIR__ . '/../../uploads/nid/back/' . $back_name;

$front_db_path = 'uploads/nid/front/' . $front_name;
$back_db_path = 'uploads/nid/back/' . $back_name;

if (!move_uploaded_file($front['tmp_name'], $front_path)) {
    setFlash('error', 'এনআইডি ফ্রন্ট ছবি আপলোড ব্যর্থ হয়েছে।');
    redirect(SITE_URL . '/submit-kyc.php');
}

if (!move_uploaded_file($back['tmp_name'], $back_path)) {
    setFlash('error', 'এনআইডি ব্যাক ছবি আপলোড ব্যর্থ হয়েছে।');
    redirect(SITE_URL . '/submit-kyc.php');
}

// if old pending/rejected/resubmit record exists, block duplicate first submission page
$stmt = $pdo->prepare("SELECT id FROM kyc_submissions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$existing = $stmt->fetch();

if ($existing) {
    setFlash('error', 'আপনার KYC ইতোমধ্যে জমা আছে। পুনরায় জমার জন্য resubmit option ব্যবহার করুন।');
    redirect(SITE_URL . '/kyc-status.php');
}

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
    $front_db_path,
    $back_db_path
]);

$stmt = $pdo->prepare("UPDATE users SET kyc_status = 'pending' WHERE id = ?");
$stmt->execute([$user_id]);

$_SESSION['kyc_status'] = 'pending';

setFlash('success', 'আপনার KYC সফলভাবে জমা হয়েছে।');
redirect(SITE_URL . '/kyc-status.php');