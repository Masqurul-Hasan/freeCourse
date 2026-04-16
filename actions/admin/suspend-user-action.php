<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($user_id <= 0) {
    setFlash('error', 'অবৈধ ইউজার আইডি।');
    redirect(SITE_URL . '/admin/users.php');
}

// Prevent admin from suspending themselves if linked as user by mistake
if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $user_id) {
    setFlash('error', 'নিজের অ্যাকাউন্ট suspend করা যাবে না।');
    redirect(SITE_URL . '/admin/users.php');
}

$stmt = $pdo->prepare("SELECT id, account_status FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'ইউজার পাওয়া যায়নি।');
    redirect(SITE_URL . '/admin/users.php');
}

$newStatus = ($user['account_status'] === 'suspended') ? 'active' : 'suspended';

try {
    $stmt = $pdo->prepare("
        UPDATE users
        SET account_status = ?
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $user_id]);

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, is_read, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");

    if ($newStatus === 'suspended') {
        $stmt->execute([
            $user_id,
            'অ্যাকাউন্ট সাময়িকভাবে বন্ধ করা হয়েছে',
            'আপনার অ্যাকাউন্ট এডমিন কর্তৃক suspended করা হয়েছে। প্রয়োজনে সাপোর্টে যোগাযোগ করুন।'
        ]);
        setFlash('success', 'ইউজার সফলভাবে suspended করা হয়েছে।');
    } else {
        $stmt->execute([
            $user_id,
            'অ্যাকাউন্ট পুনরায় চালু করা হয়েছে',
            'অভিনন্দন! আপনার অ্যাকাউন্ট আবার active করা হয়েছে।'
        ]);
        setFlash('success', 'ইউজার সফলভাবে active করা হয়েছে।');
    }

    redirect(SITE_URL . '/admin/users.php');

} catch (Exception $e) {
    setFlash('error', 'ইউজারের account status update করা যায়নি।');
    redirect(SITE_URL . '/admin/users.php');
}