<?php
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config.php';
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
<?= isset($page_title) ? e($page_title) . ' | ' . SITE_NAME : SITE_NAME; ?>
</title>

<meta name="description"
content="<?= isset($meta_description) ? e($meta_description) : 'BD Workers — বাংলাদেশভিত্তিক আধুনিক অনলাইন মাইক্রো জব প্ল্যাটফর্ম যেখানে কাজ করে আয় করা যায়।'; ?>">

<!-- Favicon -->
<link rel="icon" type="image/png" href="<?= SITE_FAVICON; ?>">
<link rel="apple-touch-icon" href="<?= SITE_FAVICON; ?>">

<!-- Open Graph (for Facebook / sharing) -->
<meta property="og:title" content="<?= isset($page_title) ? e($page_title) . ' | ' . SITE_NAME : SITE_NAME; ?>">
<meta property="og:description"
content="<?= isset($meta_description) ? e($meta_description) : 'BD Workers — বাংলাদেশভিত্তিক অনলাইন কাজের প্ল্যাটফর্ম'; ?>">
<meta property="og:image" content="<?= SITE_LOGO; ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= SITE_URL; ?>">

<!-- Google Font -->
<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Main CSS -->
<link rel="stylesheet" href="<?= SITE_URL; ?>/assets/css/style.css">

</head>

<body>