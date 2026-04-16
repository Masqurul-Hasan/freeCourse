<?php
require_once __DIR__ . '/../includes/session.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);

header('Location: login.php');
exit;