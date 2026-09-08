<?php
/**
 * Shared admin header: session guard and common assets.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUser = $_SESSION['user'] ?? null;
if (isset($_SESSION['user']) && !is_array($sessionUser)) {
    unset($_SESSION['user']);
    $sessionUser = null;
}

if (!is_array($sessionUser) || ($sessionUser['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title>Quản trị hệ thống - Movie Booking</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS CDN -->
<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
    crossorigin="anonymous">

<!-- Bootstrap Icons CDN -->
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT"
    crossorigin="anonymous">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-shell">
