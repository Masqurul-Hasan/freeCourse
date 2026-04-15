<?php

// Redirect helper
function redirect($url)
{
    header("Location: " . $url);
    exit();
}


// Generate unique user UID
function generateUserUID()
{
    return 'USR' . time() . rand(100,999);
}


// Generate referral code
function generateReferralCode($length = 8)
{
    return strtoupper(substr(md5(uniqid()),0,$length));
}


// Flash message system
function setFlash($key,$message)
{
    $_SESSION['flash'][$key] = $message;
}

function getFlash($key)
{
    if(isset($_SESSION['flash'][$key])) {

        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $msg;
    }

    return null;
}


// Sanitize output
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}