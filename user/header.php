<?php
session_start();
include '../config.php';

$user_id = $_SESSION['user_id'] ?? 0;
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

$theme = $user['theme_preference'] ?? 'light';
$font_size = $user['font_size'] ?? '16px';
$primary_color = $user['primary_color'] ?? '#007bff';
$secondary_color = $user['secondary_color'] ?? '#6c757d';
$font_color = $user['font_color'] ?? '#000000';
$bg_color = $theme === 'dark' ? '#121212' : '#ffffff';
$card_bg = $theme === 'dark' ? '#1e1e1e' : '#f8f9fa';
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Site</title>
    <style>
        body {
            background-color: <?= $bg_color ?>;
            color: <?= $font_color ?>;
            font-size: <?= $font_size ?>;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        a {
            color: <?= $primary_color ?>;
        }

        .btn {
            background-color: <?= $primary_color ?>;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-secondary {
            background-color: <?= $secondary_color ?>;
            color: #fff;
        }

        header, footer {
            background-color: <?= $primary_color ?>;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .card {
            background-color: <?= $card_bg ?>;
            border: 1px solid <?= $secondary_color ?>;
            padding: 20px;
            margin: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
