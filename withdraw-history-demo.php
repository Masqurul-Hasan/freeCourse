<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'উত্তোলন বিবরণী';
$meta_description = 'সাম্প্রতিক withdraw activity preview';

$names = [
    'Atikur Rahman', 'Borhan Mia', 'Umme Habiba', 'Anjumara', 'MD Liton Ahmed',
    'Masum', 'Roton', 'Arpita Kundu', 'Sabbir Hossain', 'Rashed Khan',
    'Saiful Islam', 'Nasrin Akter', 'Shahin Mia', 'Rakib Hasan',
    'Jannatul Ferdous', 'Mamun Hasan', 'Nusrat Jahan', 'Ruhul Amin',
    'Shamim Reza', 'Mim Akter', 'Fahim Ahmed', 'Sharmin Sultana',
    'Jubaer Hasan', 'Tania Akter', 'Kamrul Islam', 'Mizanur Rahman',
    'Mim Akter', 'রুবিনা আক্তার', 'Tanvir Ahmed', 'সাইফুল ইসলাম',
    'Rakib Hasan', 'Shirin Akter', 'সাবিহা সুলতানা', 'Arif Hossain',
    'Nasrin Akter', 'মোস্তাফিজুর রহমান', 'Raihan Islam', 'লাবিবা আক্তার',
    'Jannatul Ferdous', 'Shamim Reza', 'তানভীর হাসান', 'Farzana Yasmin',
    'Rafiqul Islam', 'নাইম হাসান', 'Sabbir Rahman', 'মেহজাবিন আক্তার',
    'Sharmin Sultana', 'Towhidul Islam', 'সিয়াম আহমেদ', 'Salma Khatun',
    'Fahim Ahmed', 'রুবেল হাসান', 'Nusrat Jahan', 'Shila Akter',
    'মিজানুর রহমান', 'Imran Hossain', 'Jubaer Hasan', 'তাসলিমা বেগম',
    'Mahmudul Hasan', 'সুমাইয়া আক্তার', 'Kamrul Islam', 'Zubair Hossain',
    'রাশেদ মাহমুদ', 'Rehana Akter', 'Nazmul Hossain', 'মাহবুব আলম',
    'Tania Akter', 'Habibur Rahman', 'সাবিনা ইয়াসমিন', 'Rumana Akter',
    'Ashraful Islam', 'শাওন হোসেন', 'Roksana Begum', 'Nabil Hossain',
    'Shahin Mia', 'জান্নাত আক্তার', 'Adnan Karim', 'Parveen Sultana',
    'সাইদুর রহমান', 'Sadia Islam', 'Mosharraf Hossain', 'মাহিরা সুলতানা',
    'Mamun Hasan', 'Minara Begum', 'Sajjad Hossain', 'ইসরাত জাহান',
    'Belal Hossain', 'Afia Tasnim', 'মুন্না হোসেন', 'Rashidul Islam',
    'Israt Jahan', 'সাফওয়ান ইসলাম', 'Sanjida Akter', 'মিতু আক্তার',
    'Abdul Karim', 'Fardin Islam', 'লিপি আক্তার', 'Sumaiya Jahan',
    'Mahfuzur Rahman', 'রিনা আক্তার', 'Sharmeen Akter', 'Nowrin Sultana',
    'Delwar Hossain', 'নুসরাত ইসলাম', 'Tahmina Akter', 'Rasel Ahmed',
    'Jahid Hasan', 'সোহেল রানা', 'Mizanur Rahman', 'Khadija Akter',
    'Sultana Parvin', 'মারুফ হোসেন', 'Rakibul Hasan', 'Tasnia Rahman',
    'Sharif Ahmed', 'তামান্না আক্তার', 'Ruhul Amin', 'Anika Rahman',
    'Hasan Mahmud', 'মোশাররফ হোসেন', 'Sadia Afrin', 'Alamgir Hossain',
    'Shakib Al Hasan', 'খাদিজা বেগম', 'Laboni Akter', 'Sohan Ahmed',
    'Maliha Chowdhury', 'রেহানা পারভীন', 'Saiful Islam', 'Ritu Akter',
    'Nayeem Hasan', 'জেসমিন আক্তার', 'Imran Hossain', 'Mousumi Akter',
    'জুবায়ের আহমেদ', 'Roksana Parvin', 'Mahmudul Hasan', 'Rafiqul Islam',
    'তানজিম হাসান', 'Farhan Ahmed', 'Mehedi Hasan', 'Sadia Jahan',
    'রিদওয়ান ইসলাম', 'Parvin Sultana', 'Sabbir Ahmed', 'Sumaiya Akter',
    'Al Amin', 'Shamima Akter', 'জান্নাতুল ইসলাম', 'Arman Hossain',
    'Rafsan Hasan', 'Nusrat Islam', 'মৌসুমী আক্তার', 'Jannat Akter',
    'Shuvo Ahmed', 'Fatema Khatun', 'Tanvir Rahman', 'Sabina Yasmin',
    'Shamim Ahmed', 'Jakia Sultana', 'Kamrul Hasan', 'Shahana Parvin'
];

$methods = ['bKash', 'Nagad', 'Rocket'];

/* majority done */
$statuses = [
    'Done','Done','Done','Done','Done','Done','Done','Done','Done','Done',
    'Processing','In Review'
];

$withdrawRows = [];

for ($i = 0; $i < 200; $i++) {
    $name = $names[array_rand($names)];
    $method = $methods[array_rand($methods)];
    $status = $statuses[array_rand($statuses)];

    $amount = rand(1000, 5000) + rand(10, 99) / 100;
    $amountFormatted = number_format($amount, 2);

    $account = '01' . rand(3, 9) . '****' . rand(100, 999);

    /* last 3 days */
    $time = date(
        'Y-m-d H:i:s',
        strtotime('-' . rand(1, 4320) . ' minutes')
    );

    $withdrawRows[] = [
        'name' => $name,
        'method' => $method,
        'account' => $account,
        'amount' => $amountFormatted,
        'status' => $status,
        'time' => $time
    ];
}

/* newest first */
usort($withdrawRows, function ($a, $b) {
    return strtotime($b['time']) <=> strtotime($a['time']);
});

/* fixed stats */
$reviewCount = 277;
$processingCount = 14;
$doneCount = 144563;

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<style>
.withdraw-live-shell{
    padding: 28px 0 48px;
}

.withdraw-live-hero{
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid #e7edf5;
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
    margin-bottom: 22px;
}

.withdraw-live-head h1{
    margin: 0 0 10px;
    font-size: clamp(30px, 4vw, 48px);
    line-height: 1.04;
    font-weight: 900;
    color: #0f172a;
}

.withdraw-live-head p{
    margin: 0;
    color: #64748b;
    line-height: 1.8;
    max-width: 840px;
}

.withdraw-stats{
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.withdraw-stat-card{
    background: #fff;
    border: 1px solid #e7edf5;
    border-radius: 22px;
    padding: 22px;
    text-align: center;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.04);
}

.withdraw-stat-card h3{
    margin: 0 0 8px;
    font-size: 34px;
    line-height: 1;
    color: #0f172a;
    font-weight: 900;
}

.withdraw-stat-card p{
    margin: 0;
    color: #5b6b85;
    line-height: 1.7;
    font-weight: 700;
}

.withdraw-table-wrap{
    background: #fff;
    border: 1px solid #e7edf5;
    border-radius: 26px;
    padding: 24px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
    overflow: hidden;
}

.withdraw-table-head{
    margin-bottom: 18px;
}

.withdraw-table-head h2{
    margin: 0 0 8px;
    font-size: 32px;
    line-height: 1.06;
    font-weight: 900;
    color: #0f172a;
}

.withdraw-table-head p{
    margin: 0;
    color: #64748b;
    line-height: 1.8;
}

.withdraw-table{
    width: 100%;
    border-collapse: collapse;
}

.withdraw-table thead th{
    text-align: left;
    font-size: 14px;
    color: #475569;
    padding: 14px 12px;
    border-bottom: 1px solid #dbe4ee;
    background: #f8fbff;
}

.withdraw-table tbody td{
    padding: 16px 12px;
    border-bottom: 1px solid #eef2f7;
    vertical-align: top;
    color: #0f172a;
    font-size: 14px;
}

.withdraw-table tbody tr:last-child td{
    border-bottom: none;
}

.withdraw-method{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    color: #fff;
}

.method-bkash{ background: #e2136e; }
.method-nagad{ background: #f97316; }
.method-rocket{ background: #8b5cf6; }

.withdraw-status{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

.status-review{
    background: rgba(245, 158, 11, 0.15);
    color: #b45309;
}

.status-processing{
    background: rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
}

.status-done{
    background: rgba(34, 197, 94, 0.12);
    color: #15803d;
}

.withdraw-cta{
    margin-top: 22px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.withdraw-btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 50px;
    padding: 0 20px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 800;
}

.withdraw-btn-primary{
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
}

.withdraw-btn-light{
    background: #fff;
    color: #0f172a;
    border: 1px solid #dbe4ee;
}

@media (max-width: 1024px){
    .withdraw-stats{
        grid-template-columns: 1fr;
    }
}

@media (max-width: 860px){
    .withdraw-table-wrap{
        overflow-x: auto;
    }

    .withdraw-table{
        min-width: 760px;
    }
}

@media (max-width: 700px){
    .withdraw-live-shell{
        padding: 20px 0 36px;
    }

    .withdraw-live-hero,
    .withdraw-table-wrap{
        border-radius: 20px;
        padding: 18px;
    }

    .withdraw-cta{
        flex-direction: column;
    }

    .withdraw-btn{
        width: 100%;
    }
}
</style>

<main class="withdraw-live-shell">
    <div class="container">

        <section class="withdraw-live-hero">
            <div class="withdraw-live-head">
                <h1>উত্তোলন বিবরণী</h1>
                <p>
                    সাম্প্রতিক withdraw activity preview এখানে দেখানো হচ্ছে। account খুলে এবং কাজ complete করে
                    wallet balance জমা হওয়ার পর আপনিও withdraw request করতে পারবেন।
                </p>
            </div>
        </section>

        <section class="withdraw-stats">
            <div class="withdraw-stat-card">
                <h3><?= $reviewCount; ?></h3>
                <p>Request In Review</p>
            </div>
            <div class="withdraw-stat-card">
                <h3><?= $processingCount; ?></h3>
                <p>Payment Request Processing</p>
            </div>
            <div class="withdraw-stat-card">
                <h3><?= $doneCount; ?></h3>
                <p>Request Approved</p>
            </div>
        </section>

        <section class="withdraw-table-wrap">
            <div class="withdraw-table-head">
                <h2>Recent Withdraw History</h2>
                <p>সাম্প্রতিক withdraw activity preview দেখুন।</p>
            </div>

            <table class="withdraw-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Account</th>
                        <th>Amount & Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawRows as $row): ?>
                        <tr>
                            <td>
                                <strong><?= e($row['name']); ?></strong>
                            </td>

                            <td>
                                <span class="withdraw-method <?= strtolower($row['method']) === 'bkash' ? 'method-bkash' : (strtolower($row['method']) === 'nagad' ? 'method-nagad' : 'method-rocket'); ?>">
                                    <?= e($row['method']); ?>
                                </span>
                                <div style="margin-top: 8px; color: #475569;">
                                    <?= e($row['account']); ?>
                                </div>
                            </td>

                            <td>
                                <div style="font-weight: 800; margin-bottom: 8px;">
                                    <?= e($row['amount']); ?> BDT
                                </div>

                                <?php if ($row['status'] === 'In Review'): ?>
                                    <span class="withdraw-status status-review">In Review</span>
                                <?php elseif ($row['status'] === 'Processing'): ?>
                                    <span class="withdraw-status status-processing">Processing</span>
                                <?php else: ?>
                                    <span class="withdraw-status status-done">Done</span>
                                <?php endif; ?>
                            </td>

                            <td><?= e($row['time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="withdraw-cta">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="<?= SITE_URL; ?>/withdraw.php" class="withdraw-btn withdraw-btn-primary">এখনই Withdraw করুন</a>
                    <a href="<?= SITE_URL; ?>/dashboard.php" class="withdraw-btn withdraw-btn-light">ড্যাশবোর্ডে ফিরে যান</a>
                <?php else: ?>
                    <a href="<?= SITE_URL; ?>/register.php" class="withdraw-btn withdraw-btn-primary">রেজিস্টার করুন</a>
                    <a href="<?= SITE_URL; ?>/login.php" class="withdraw-btn withdraw-btn-light">লগইন করুন</a>
                <?php endif; ?>
            </div>
        </section>

    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>