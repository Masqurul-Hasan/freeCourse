<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

if (empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/tasks.php');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$taskId = (int)($_POST['task_id'] ?? 0);
$proofNote = trim($_POST['proof_note'] ?? '');

if ($userId <= 0 || $taskId <= 0) {
    setFlash('error', 'Invalid task request.');
    redirect(SITE_URL . '/tasks.php');
}

if (!isset($_FILES['proof_image'])) {
    setFlash('error', 'Proof image field not found.');
    redirect(SITE_URL . '/tasks.php');
}

$file = $_FILES['proof_image'];

if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds MAX_FILE_SIZE.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];

    $message = $uploadErrors[$file['error']] ?? 'Unknown upload error.';
    setFlash('error', 'Proof image upload error: ' . $message);
    redirect(SITE_URL . '/tasks.php');
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

$originalName = $file['name'] ?? '';
$tmpName = $file['tmp_name'] ?? '';
$fileSize = (int)($file['size'] ?? 0);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions, true)) {
    setFlash('error', 'Only JPG, JPEG, PNG, WEBP files are allowed.');
    redirect(SITE_URL . '/tasks.php');
}

if ($fileSize <= 0 || $fileSize > $maxFileSize) {
    setFlash('error', 'Image size must be under 2MB.');
    redirect(SITE_URL . '/tasks.php');
}

if (!is_uploaded_file($tmpName)) {
    setFlash('error', 'Invalid uploaded file.');
    redirect(SITE_URL . '/tasks.php');
}

$uploadDir = __DIR__ . '/../../uploads/task-proofs/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        setFlash('error', 'Upload folder তৈরি করা যায়নি.');
        redirect(SITE_URL . '/tasks.php');
    }
}

if (!is_writable($uploadDir)) {
    setFlash('error', 'Upload folder writable নয়. Path: ' . $uploadDir);
    redirect(SITE_URL . '/tasks.php');
}

$fileName = 'task_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$destination = $uploadDir . $fileName;
$dbPath = 'uploads/task-proofs/' . $fileName;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM tasks
        WHERE id = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        $pdo->rollBack();
        setFlash('error', 'Task পাওয়া যায়নি।');
        redirect(SITE_URL . '/tasks.php');
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM task_submissions
        WHERE task_id = ? AND user_id = ? AND status IN ('pending','approved')
        LIMIT 1
    ");
    $stmt->execute([$taskId, $userId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $pdo->rollBack();
        setFlash('error', 'এই task আপনি আগেই submit করেছেন।');
        redirect(SITE_URL . '/tasks.php');
    }

    if (!move_uploaded_file($tmpName, $destination)) {
        $pdo->rollBack();
        setFlash('error', 'Proof image upload failed. Destination: ' . $destination);
        redirect(SITE_URL . '/tasks.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO task_submissions (
            task_id,
            user_id,
            proof_note,
            proof_image,
            status,
            submitted_at
        ) VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $taskId,
        $userId,
        $proofNote !== '' ? $proofNote : null,
        $dbPath
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
        'Task Submitted',
        'আপনার task submission সফলভাবে জমা হয়েছে। Admin review করার পর reward দেওয়া হবে।'
    ]);

    $pdo->commit();

    setFlash('success', 'Task submitted successfully.');
    redirect(SITE_URL . '/tasks.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (isset($destination) && file_exists($destination)) {
        @unlink($destination);
    }

    setFlash('error', 'Task submit error: ' . $e->getMessage());
    redirect(SITE_URL . '/tasks.php');
}