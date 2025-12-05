<?php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Aokiji's Ice Gear Rentals</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand img {
            height: 32px;
            width: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="images/logo.jpg" alt="Aokiji Logo">
            <span class="fw-bold">Aokiji's Ice Gear</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="equipment.php">Browse Gear</a></li>
                <li class="nav-item"><a class="nav-link" href="new_rental.php">New Rental</a></li>
                <li class="nav-item"><a class="nav-link" href="return_rental.php">Return Rental</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container mb-4">
