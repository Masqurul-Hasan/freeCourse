<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <div class="footer-brand"><?= e(SITE_NAME); ?></div>
            <p class="footer-text">
                বাংলা ভাষাভিত্তিক আধুনিক, নিরাপদ এবং ব্যবহারবান্ধব মাইক্রোজব প্ল্যাটফর্ম।
            </p>
        </div>

        <div>
            <h4>গুরুত্বপূর্ণ লিংক</h4>
            <a href="<?= SITE_URL; ?>/privacy.php">প্রাইভেসি পলিসি</a>
            <a href="<?= SITE_URL; ?>/terms.php">শর্তাবলী</a>
            <a href="<?= SITE_URL; ?>/contact.php">যোগাযোগ</a>
        </div>
    </div>

    <div class="footer-bottom">
        © <?= date('Y'); ?> <?= e(SITE_NAME); ?>. সর্বস্বত্ব সংরক্ষিত।
    </div>
</footer>
</body>
</html>