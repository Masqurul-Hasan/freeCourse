<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'হোম';
$meta_description = 'BD Workers - বাংলা মাইক্রো ওয়ার্ক earning platform';

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

.home-steps{
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.home-step{
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid #e7edf5;
    border-radius: 20px;
    padding: 22px;
}

.home-step-number{
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #2563eb;
    color: #fff;
    font-weight: 900;
    margin-bottom: 14px;
}

.home-step h3{
    margin: 0 0 10px;
    color: #0f172a;
    font-weight: 900;
}

.home-step p{
    margin: 0;
    color: #5b6b85;
    line-height: 1.8;
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
    .home-grid,
    .home-steps{
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px){
    .home-shell{
        padding: 20px 0 36px;
    }

    .home-hero,
    .home-section,
    .home-cta{
        border-radius: 20px;
        padding: 20px;
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

        <section class="home-section">
            <div class="home-section-head">
                <h2>কীভাবে কাজ করে</h2>
                <p>শুরু থেকে earning পর্যন্ত পুরো process খুব সহজ রাখা হয়েছে।</p>
            </div>

            <div class="home-steps">
                <div class="home-step">
                    <div class="home-step-number">1</div>
                    <h3>রেজিস্টার করুন</h3>
                    <p>একটি account খুলুন এবং profile setup করুন।</p>
                </div>

                <div class="home-step">
                    <div class="home-step-number">2</div>
                    <h3>Task complete করুন</h3>
                    <p>ভিডিও দেখা, ad click করা বা ছোট task complete করে proof submit করুন।</p>
                </div>

                <div class="home-step">
                    <div class="home-step-number">3</div>
                    <h3>Earning তুলুন</h3>
                    <p>Approved reward wallet-এ যোগ হবে, পরে withdraw request করতে পারবেন।</p>
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