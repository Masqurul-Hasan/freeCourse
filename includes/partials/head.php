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
    <title><?= isset($page_title) ? e($page_title) . ' | ' . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?= isset($meta_description) ? e($meta_description) : 'বাংলা ভাষাভিত্তিক আধুনিক মাইক্রোজব প্ল্যাটফর্ম'; ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= SITE_URL; ?>/assets/css/style.css">
</head>
<body>