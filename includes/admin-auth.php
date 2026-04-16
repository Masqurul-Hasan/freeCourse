<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['admin_id'])) {
    setFlash('error', 'প্রথমে এডমিন লগইন করুন।');
    redirect(SITE_URL . '/admin/login.php');
}