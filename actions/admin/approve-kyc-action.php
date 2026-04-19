<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/pending-kyc.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'অবৈধ রিকুয়েস্ট।');
    redirect(SITE_URL . '/admin/pending-kyc.php');
}

$kyc_id = (int) ($_POST['kyc_id'] ?? 0);
$user_id = (int) ($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    setFlash('error', 'অবৈধ তথ্য।');
    redirect(SITE_URL . '/admin/pending-kyc.php');
}

try {
    $pdo->beginTransaction();

    if ($kyc_id > 0) {
        $stmt = $pdo->prepare("
            UPDATE kyc_submissions
            SET status = 'approved',
                admin_comment = NULL,
                reviewed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$kyc_id]);
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET kyc_status = 'approved'
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);

    /* =========================================
       GET USER INFO
       ========================================= */
    $stmt = $pdo->prepare("
        SELECT id, referred_by
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        throw new Exception('User not found.');
    }

    /* =========================================
       GIVE BONUS AFTER KYC APPROVE (Option B)
       - no referral => 100
       - with referral => 150
       prevent duplicate bonus
       ========================================= */
    $bonusAmount = !empty($currentUser['referred_by']) ? 150 : 100;
    $walletSource = !empty($currentUser['referred_by']) ? 'referral_bonus' : 'signup_bonus';
    $walletDescription = !empty($currentUser['referred_by'])
        ? 'Referral signup bonus after KYC approval'
        : 'Signup bonus after KYC approval';

    $stmt = $pdo->prepare("
        SELECT id
        FROM wallet_transactions
        WHERE user_id = ?
          AND source IN ('signup_bonus', 'referral_bonus')
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $alreadyBonusGiven = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alreadyBonusGiven) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET wallet_balance = wallet_balance + ?
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$bonusAmount, $user_id]);

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
    }

    /* user notification */
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
        'KYC অনুমোদিত হয়েছে',
        !empty($currentUser['referred_by'])
            ? 'অভিনন্দন! আপনার KYC approved হয়েছে এবং আপনার wallet-এ ৳150.00 referral signup bonus যোগ করা হয়েছে।'
            : 'অভিনন্দন! আপনার KYC approved হয়েছে এবং আপনার wallet-এ ৳100.00 signup bonus যোগ করা হয়েছে।'
    ]);

    /* =========================================
       REFERRAL PAYOUT CREATION
       referred_by stores referral code string
       ========================================= */
    if (!empty($currentUser['referred_by'])) {
        $referredByCode = trim((string)$currentUser['referred_by']);

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE referral_code = ?
            LIMIT 1
        ");
        $stmt->execute([$referredByCode]);
        $referrer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($referrer) {
            $referrerId = (int)$referrer['id'];

            $stmt = $pdo->prepare("
                INSERT IGNORE INTO referral_payouts
                (referrer_user_id, referred_user_id, amount, status, created_at)
                VALUES (?, ?, 20.00, 'pending', NOW())
            ");
            $stmt->execute([
                $referrerId,
                $user_id
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
                $referrerId,
                'Referral Payout Pending',
                'আপনার referred user-এর KYC approved হয়েছে। আপনার জন্য ৳20.00 bKash payout pending করা হয়েছে।'
            ]);
        }
    }

    $pdo->commit();

    setFlash('success', 'KYC সফলভাবে অনুমোদন করা হয়েছে।');
    redirect(SITE_URL . '/admin/pending-kyc.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', 'KYC approve error: ' . $e->getMessage());
    redirect(SITE_URL . '/admin/kyc-details.php?user_id=' . $user_id);
}