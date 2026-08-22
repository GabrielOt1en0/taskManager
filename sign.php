<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['Username'] ?? '');
    $password = trim($_POST['Password'] ?? '');

    if (empty($username) || empty($password)) {
        die("Username and password are required.");
    }
    if(strlen($username) <3 || strlen($password) <6) {
        die("Username must be >= 3 chars, password >=6 chars");
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt ->execute([$username]);
    if ($stmt->fetch()){
        die("Username already taken.");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username,password) VALUES (?, ?)");

    if ($stmt->excecute([$username,$hashedPassword])) {
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit;
    }
}
?>
