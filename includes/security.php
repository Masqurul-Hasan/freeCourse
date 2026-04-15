<?php

// Generate CSRF token
function csrf_token()
{
    if(empty($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    }

    return $_SESSION['csrf_token'];
}


// Validate CSRF token
function verify_csrf($token)
{
    if(isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {

        return true;

    }

    return false;
}