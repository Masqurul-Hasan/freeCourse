<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/notifications.php');
}

$userId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);

    setFlash('success', 'সব notification read হিসেবে mark করা হয়েছে.');
    redirect(SITE_URL . '/notifications.php');

} catch (Throwable $e) {
    setFlash('error', 'Notification update error: ' . $e->getMessage());
    redirect(SITE_URL . '/notifications.php');
}