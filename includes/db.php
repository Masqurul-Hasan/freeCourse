<?php

$host = "localhost";
$dbname = "freecourse_db";
$user = "hasan";
$pass = "Hasan12345";

try {

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch(PDOException $e) {

    die("Database Connection Failed: " . $e->getMessage());

}