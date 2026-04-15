<?php

if (session_status() === PHP_SESSION_NONE) {

    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false,
        'cookie_samesite' => 'Strict'
    ]);

}