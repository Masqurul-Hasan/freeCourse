<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'হোম';
$meta_description = 'BD Workers - বাংলা মাইক্রো ওয়ার্ক earning platform';

$recentJobs = [
    ['code' => 'VLX004X', 'done' => 12, 'total' => 320, 'reward' => '12.00', 'time' => '6 minutes ago'],
    ['code' => 'BLQ771X', 'done' => 58, 'total' => 640, 'reward' => '18.00', 'time' => '14 minutes ago'],
    ['code' => 'TUX119X', 'done' => 34, 'total' => 210, 'reward' => '15.00', 'time' => '18 minutes ago'],
    ['code' => 'BTM880X', 'done' => 92, 'total' => 520, 'reward' => '20.00', 'time' => '25 minutes ago'],
    ['code' => 'IMT004X', 'done' => 44, 'total' => 300, 'reward' => '22.00', 'time' => '32 minutes ago'],
    ['code' => 'LWS553X', 'done' => 11, 'total' => 150, 'reward' => '10.00', 'time' => '40 minutes ago'],
    ['code' => 'KRD291X', 'done' => 73, 'total' => 410, 'reward' => '24.00', 'time' => '1 hour ago'],
    ['code' => 'PXN663X', 'done' => 28, 'total' => 200, 'reward' => '16.00', 'time' => '1 hour ago'],
    ['code' => 'DMT005X', 'done' => 66, 'total' => 380, 'reward' => '27.00', 'time' => '2 hours ago'],
    ['code' => 'ZRX221X', 'done' => 19, 'total' => 260, 'reward' => '30.00', 'time' => '2 hours ago'],
];

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/header.php';
?>

<style>
.home-shell{
    padding: 28px 0 48px;
}

.home-hero{
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid #e7edf5;
    border-radius: 28px;
    padding: 34px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
    margin-bottom: 22px;
}

.home-hero-grid{
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 22px;
    align-items: center;
}

.home-eyebrow{
    margin: 0 0 10px;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .14em;
    color: #2563eb;
}

.home-title{
    margin: 0;
    font-size: clamp(32px, 5vw, 54px);
    line-height: 1.05;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -.03em;
}

.home-subtitle{
    margin: 16px 0 0;
    font-size: 16px;
    line-height: 1.85;
    color: #5b6b85;
    max-width: 760px;
}

.home-actions{
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 22px;
}

.home-btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 52px;
    padding: 0 22px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 800;
    transition: .25s ease;
}

.home-btn-primary{
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.20);
}

.home-btn-primary:hover{
    color: #fff;
    transform: translateY(-1px);
}

.home-btn-light{
    background: #fff;
    color: #0f172a;
    border: 1px solid #dbe4ee;
}

.home-btn-light:hover{
    background: #f8fbff;
    color: #0f172a;
}

.home-highlight{
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
    color: #fff;
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 16px 36px rgba(30, 64, 175, 0.22);
}

.home-highlight h3{
    margin: 0 0 10px;
    font-size: 22px;
    font-weight: 900;
}

.home-highlight p{
    margin: 0;
    color: rgba(255,255,255,.84);
    line-height: 1.8;
}

.home-points{
    display: grid;
    gap: 10px;
    margin-top: 16px;
}

.home-point{
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-size: 14px;
    line-height: 1.7;
    color: rgba(255,255,255,.92);
}

.home-point-dot{
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #34d399;
    margin-top: 8px;
    flex: 0 0 auto;
}

.home-grid{
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.home-card{
    background: #fff;
    border: 1px solid #e7edf5;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.04);
}

.home-card h2,
.home-card h3{
    margin: 0 0 10px;
    color: #0f172a;
    font-weight: 900;
}

.home-card p{
    margin: 0;
    color: #5b6b85;
    line-height: 1.8;
}

.home-section{
    background: #fff;
    border: 1px solid #e7edf5;
    border-radius: 26px;
    padding: 26px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
    margin-bottom: 22px;
}

.home-section-head{
    margin-bottom: 18px;
}

.home-section-head h2{
    margin: 0 0 8px;
    font-size: clamp(26px, 3vw, 38px);
    line-height: 1.08;
    font-weight: 900;
    color: #0f172a;
}

.home-section-head p{
    margin: 0;
    color: #64748b;
    line-height: 1.8;
}

.recent-activity-section{
    padding: 4px 0 28px;
}

.recent-activity-wrap{
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    margin-bottom: 22px;
}

.recent-activity-head{
    color: #fff;
    margin-bottom: 20px;
}

.recent-job-reward::after{
    content: " ৳";
    font-weight: 600;
    margin-left: 3px;
}

.recent-activity-eyebrow{
    display: inline-block;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
    margin-bottom: 8px;
    color: #d8ecff;
}

.recent-activity-head h2{
    margin: 0 0 8px;
    font-size: clamp(30px, 3vw, 48px);
    line-height: 1.02;
    font-weight: 900;
    color: #fff;
}

.recent-activity-head p{
    margin: 0;
    color: rgba(255,255,255,.82);
    font-size: 15px;
    line-height: 1.7;
}

.recent-activity-box{
    background: #edf9ef;
    border: 1px solid #d5eedb;
    border-radius: 24px;
    padding: 16px;
}

.recent-job-row{
    background: #fff;
    border: 1px solid #dceee0;
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 10px;
}

.recent-job-row:last-child{
    margin-bottom: 0;
}

.recent-job-top{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.recent-view-btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 30px;
    padding: 0 12px;
    background: #47c96f;
    color: #fff;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}

.recent-view-btn:hover{
    color: #fff;
    opacity: .92;
}

.recent-job-time{
    font-size: 13px;
    color: #2f6dfc;
    font-weight: 600;
}

.recent-job-main{
    display: grid;
    grid-template-columns: 180px 1fr 110px;
    align-items: center;
    gap: 18px;
}

.recent-job-code{
    font-size: 14px;
    font-weight: 700;
    color: #7b8595;
    letter-spacing: .04em;
}

.recent-job-progress-wrap{
    width: 100%;
}

.recent-job-progress-text{
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    color: #2f6dfc;
    margin-bottom: 8px;
}

.recent-job-progress{
    width: 100%;
    height: 4px;
    background: #dfe5ec;
    border-radius: 999px;
    overflow: hidden;
}

.recent-job-progress-bar{
    height: 100%;
    background: #2f6dfc;
    border-radius: 999px;
}

.recent-job-reward{
    text-align: right;
    font-size: 28px;
    font-weight: 500;
    color: #23955b;
}

.recent-activity-note{
    color: rgba(255,255,255,.84);
    font-size: 14px;
    line-height: 1.7;
    margin-top: 14px;
    text-align: center;
}

.home-cta{
    background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
    border-radius: 28px;
    padding: 28px;
    color: #fff;
    box-shadow: 0 18px 46px rgba(30, 64, 175, 0.22);
}

.home-cta h2{
    margin: 0 0 10px;
    font-size: clamp(28px, 3vw, 40px);
    line-height: 1.08;
    font-weight: 900;
}

.home-cta p{
    margin: 0;
    color: rgba(255,255,255,.84);
    line-height: 1.8;
    max-width: 820px;
}

.home-cta-actions{
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.home-cta .home-btn-light{
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.16);
    color: #fff;
}

.home-cta .home-btn-light:hover{
    background: rgba(255,255,255,.16);
    color: #fff;
}

@media (max-width: 1024px){
    .home-hero-grid,
    .home-grid{
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px){
    .recent-job-main{
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .recent-job-code{
        text-align: left;
    }

    .recent-job-reward{
        text-align: left;
        font-size: 24px;
    }
}

@media (max-width: 700px){
    .home-shell{
        padding: 20px 0 36px;
    }

    .home-hero,
    .home-section,
    .home-cta,
    .recent-activity-wrap{
        border-radius: 20px;
        padding: 20px;
    }

    .recent-activity-box{
        border-radius: 18px;
        padding: 10px;
    }

    .recent-job-row{
        padding: 12px;
    }

    .recent-job-top{
        gap: 12px;
        align-items: flex-start;
        flex-direction: column;
    }

    .home-actions,
    .home-cta-actions{
        flex-direction: column;
    }

    .home-btn{
        width: 100%;
    }
}
</style>

<main class="home-shell">
    <div class="container">

        <section class="home-hero">
            <div class="home-hero-grid">
                <div>
                    <p class="home-eyebrow">BD Workers</p>
                    <h1 class="home-title">অল্প সময় কাজ করে অনলাইনে earning শুরু করুন</h1>
                    <p class="home-subtitle">
                        BD Workers হলো একটি সহজ micro work platform যেখানে user ছোট ছোট online task
                        complete করে earning করতে পারে। প্রতিদিন ১–৬ ঘণ্টা কাজ করে
                        প্রায় <strong>১০০ থেকে ১০০০ টাকা</strong> পর্যন্ত earning করার সুযোগ আছে।
                    </p>

                    <div class="home-actions">
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <a href="<?= SITE_URL; ?>/dashboard.php" class="home-btn home-btn-primary">ড্যাশবোর্ডে যান</a>
                            <a href="<?= SITE_URL; ?>/tasks.php" class="home-btn home-btn-light">Tasks দেখুন</a>
                        <?php else: ?>
                            <a href="<?= SITE_URL; ?>/register.php" class="home-btn home-btn-primary">এখনই রেজিস্টার করুন</a>
                            <a href="<?= SITE_URL; ?>/login.php" class="home-btn home-btn-light">লগইন করুন</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="home-highlight">
                    <h3>কী ধরনের কাজ থাকবে?</h3>
                    <p>
                        Beginner-friendly micro task system যেখানে খুব কঠিন কিছু নয় —
                        সহজ instruction follow করেই কাজ করা যাবে।
                    </p>

                    <div class="home-points">
                        <div class="home-point">
                            <span class="home-point-dot"></span>
                            <span>ভিডিও দেখা</span>
                        </div>
                        <div class="home-point">
                            <span class="home-point-dot"></span>
                            <span>Ad click করা</span>
                        </div>
                        <div class="home-point">
                            <span class="home-point-dot"></span>
                            <span>ছোট ছোট online task complete করা</span>
                        </div>
                        <div class="home-point">
                            <span class="home-point-dot"></span>
                            <span>Proof submit করে reward পাওয়া</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-grid">
            <div class="home-card">
                <h3>সহজে শুরু</h3>
                <p>
                    Account খুলে, login করে এবং available tasks দেখে খুব সহজে earning শুরু করা যাবে।
                </p>
            </div>

            <div class="home-card">
                <h3>কম সময়ে কাজ</h3>
                <p>
                    প্রতিদিন ১–৬ ঘণ্টা সময় দিয়েই ছোট ছোট micro work complete করার সুযোগ থাকবে।
                </p>
            </div>

            <div class="home-card">
                <h3>Wallet + Withdraw</h3>
                <p>
                    Approved reward wallet-এ যোগ হবে, পরে withdraw request দিয়ে টাকা তোলা যাবে।
                </p>
            </div>
        </section>

        <section class="recent-activity-section">
            <div class="recent-activity-wrap">
                <div class="recent-activity-head">
                    <span class="recent-activity-eyebrow">BD WORKERS</span>
                    <h2>Recently Posted Jobs</h2>
                    <p>সাম্প্রতিক sample work preview দেখুন</p>
                </div>

                <div class="recent-activity-box">
                    <?php foreach ($recentJobs as $job): ?>
                        <?php
                            $percent = 0;
                            if ((int)$job['total'] > 0) {
                                $percent = min(100, max(0, ($job['done'] / $job['total']) * 100));
                            }
                        ?>
                        <div class="recent-job-row">
                            <div class="recent-job-top">
                                <a href="<?= SITE_URL; ?>/login.php" class="recent-view-btn">View</a>
                                <span class="recent-job-time"><?= e($job['time']); ?></span>
                            </div>

                            <div class="recent-job-main">
                                <div class="recent-job-code"><?= e($job['code']); ?></div>

                                <div class="recent-job-progress-wrap">
                                    <div class="recent-job-progress-text">
                                        <?= (int)$job['done']; ?> OF <?= (int)$job['total']; ?>
                                    </div>
                                    <div class="recent-job-progress">
                                        <div class="recent-job-progress-bar" style="width: <?= number_format($percent, 2); ?>%;"></div>
                                    </div>
                                </div>

                                <div class="recent-job-reward"><?= e($job['reward']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="recent-activity-note">
                    সম্পূর্ণ work details দেখতে login / register করুন।
                </div>
            </div>
        </section>

        <section class="home-cta">
            <h2>আজই earning শুরু করুন</h2>
            <p>
                আপনি যদি সহজ micro work করে earning করতে চান, তাহলে এখনই account খুলুন।
                ছোট ছোট কাজ complete করে wallet-এ balance জমা করুন এবং পরে withdraw করুন।
            </p>

            <div class="home-cta-actions">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="<?= SITE_URL; ?>/tasks.php" class="home-btn home-btn-primary">Tasks শুরু করুন</a>
                    <a href="<?= SITE_URL; ?>/wallet.php" class="home-btn home-btn-light">Wallet দেখুন</a>
                <?php else: ?>
                    <a href="<?= SITE_URL; ?>/register.php" class="home-btn home-btn-primary">রেজিস্টার করুন</a>
                    <a href="<?= SITE_URL; ?>/login.php" class="home-btn home-btn-light">লগইন করুন</a>
                <?php endif; ?>
            </div>
        </section>

    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>