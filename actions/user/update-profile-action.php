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
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($userId <= 0) {
    redirect(SITE_URL . '/login.php');
}

if ($name === '' || $phone === '') {
    setFlash('error', 'নাম এবং মোবাইল নাম্বার প্রয়োজন।');
    redirect(SITE_URL . '/profile.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $pdo->rollBack();
        session_unset();
        session_destroy();
        redirect(SITE_URL . '/login.php');
    }

    /* duplicate phone check */
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE phone = ? AND id != ?
        LIMIT 1
    ");
    $stmt->execute([$phone, $userId]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        setFlash('error', 'এই মোবাইল নাম্বার অন্য একটি একাউন্টে ব্যবহৃত হচ্ছে।');
        redirect(SITE_URL . '/profile.php');
    }

    /* duplicate email check */
    if ($email !== '') {
        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ? AND id != ?
            LIMIT 1
        ");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->rollBack();
            setFlash('error', 'এই ইমেইল অন্য একটি একাউন্টে ব্যবহৃত হচ্ছে।');
            redirect(SITE_URL . '/profile.php');
        }
    }

    $newProfileImage = $user['profile_image'] ?? null;
    $newAbsolutePath = null;

    /* profile image upload */
    if (!empty($_FILES['profile_image']['name'])) {
        $file = $_FILES['profile_image'];

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $pdo->rollBack();
            setFlash('error', 'প্রোফাইল ছবি আপলোড ব্যর্থ হয়েছে।');
            redirect(SITE_URL . '/profile.php');
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        $originalName = $file['name'] ?? '';
        $tmpName = $file['tmp_name'] ?? '';
        $fileSize = (int)($file['size'] ?? 0);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            $pdo->rollBack();
            setFlash('error', 'শুধুমাত্র JPG, JPEG, PNG, WEBP ফাইল গ্রহণযোগ্য।');
            redirect(SITE_URL . '/profile.php');
        }

        if ($fileSize <= 0 || $fileSize > $maxFileSize) {
            $pdo->rollBack();
            setFlash('error', 'প্রোফাইল ছবির size 2MB এর মধ্যে হতে হবে।');
            redirect(SITE_URL . '/profile.php');
        }

        if (!is_uploaded_file($tmpName)) {
            $pdo->rollBack();
            setFlash('error', 'অবৈধ আপলোড ফাইল।');
            redirect(SITE_URL . '/profile.php');
        }

        $uploadDir = __DIR__ . '/../../uploads/profile-images/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                $pdo->rollBack();
                setFlash('error', 'Profile image folder তৈরি করা যায়নি।');
                redirect(SITE_URL . '/profile.php');
            }
        }

        if (!is_writable($uploadDir)) {
            $pdo->rollBack();
            setFlash('error', 'Profile image folder writable নয়।');
            redirect(SITE_URL . '/profile.php');
        }

        $fileName = 'profile_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $newAbsolutePath = $uploadDir . $fileName;
        $newProfileImage = 'uploads/profile-images/' . $fileName;

        if (!move_uploaded_file($tmpName, $newAbsolutePath)) {
            $pdo->rollBack();
            setFlash('error', 'প্রোফাইল ছবি save করা যায়নি।');
            redirect(SITE_URL . '/profile.php');
        }
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET
            name = ?,
            phone = ?,
            email = ?,
            profile_image = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([
        $name,
        $phone,
        $email !== '' ? $email : null,
        $newProfileImage,
        $userId
    ]);

    /* optional notification */
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
        'Profile Updated',
        'আপনার profile information সফলভাবে update করা হয়েছে।'
    ]);

    $pdo->commit();

    /* delete old image after commit */
    if (
        !empty($_FILES['profile_image']['name']) &&
        !empty($user['profile_image']) &&
        $user['profile_image'] !== $newProfileImage
    ) {
        $oldAbsolutePath = __DIR__ . '/../../' . ltrim((string)$user['profile_image'], '/');
        if (file_exists($oldAbsolutePath)) {
            @unlink($oldAbsolutePath);
        }
    }

    $_SESSION['user_name'] = $name;
    $_SESSION['user_phone'] = $phone;

    setFlash('success', 'Profile successfully updated.');
    redirect(SITE_URL . '/profile.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (!empty($newAbsolutePath) && file_exists($newAbsolutePath)) {
        @unlink($newAbsolutePath);
    }

    setFlash('error', 'Profile update error: ' . $e->getMessage());
    redirect(SITE_URL . '/profile.php');
}