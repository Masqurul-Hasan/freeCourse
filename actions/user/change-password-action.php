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
    redirect(SITE_URL . '/profile.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট। আবার চেষ্টা করুন।');
    redirect(SITE_URL . '/profile.php');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmNewPassword = $_POST['confirm_new_password'] ?? '';

if ($userId <= 0) {
    redirect(SITE_URL . '/login.php');
}

if ($currentPassword === '' || $newPassword === '' || $confirmNewPassword === '') {
    setFlash('error', 'সব password field পূরণ করুন।');
    redirect(SITE_URL . '/profile.php');
}

if ($newPassword !== $confirmNewPassword) {
    setFlash('error', 'নতুন password এবং confirm password মিলছে না।');
    redirect(SITE_URL . '/profile.php');
}

if (strlen($newPassword) < 6) {
    setFlash('error', 'নতুন password কমপক্ষে 6 অক্ষরের হতে হবে।');
    redirect(SITE_URL . '/profile.php');
}

try {
    $stmt = $pdo->prepare("
        SELECT id, password
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

    /*
      Important:
      Your current project appears to store plain passwords.
      So we compare directly.
      If later you use password_hash(), replace with password_verify().
    */
    if ((string)$user['password'] !== (string)$currentPassword) {
        setFlash('error', 'Current password সঠিক নয়।');
        redirect(SITE_URL . '/profile.php');
    }

    if ((string)$currentPassword === (string)$newPassword) {
        setFlash('error', 'নতুন password আগের password-এর মতো হতে পারবে না।');
        redirect(SITE_URL . '/profile.php');
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET password = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([
        $newPassword,
        $userId
    ]);

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
        $userId,
        'Password Changed',
        'আপনার account password সফলভাবে change করা হয়েছে।'
    ]);

    setFlash('success', 'Password successfully changed.');
    redirect(SITE_URL . '/profile.php');

} catch (Throwable $e) {
    setFlash('error', 'Password change error: ' . $e->getMessage());
    redirect(SITE_URL . '/profile.php');
}