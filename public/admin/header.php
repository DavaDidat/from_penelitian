<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peneliti - Klasifikasi Sentimen AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f0ec;
            --card-bg: #ffffff;
            --border-color: #111111;
            --primary-color: #b388ff; 
            --secondary-color: #ffeb3b;
            --success-color: #00e676; 
            --border-width: 3px;
            --shadow-offset: 6px;
        }
        body {
            background-color: var(--bg-color);
            font-family: 'Space Grotesk', sans-serif;
            color: var(--border-color);
            overflow-x: hidden;
        }
        
        /* Layout components */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--card-bg);
            border-right: var(--border-width) solid var(--border-color);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        /* Typography */
        h1, h2, h3, h4, h5 {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        /* Nav links */
        .nav-link {
            font-weight: 700;
            color: var(--border-color);
            border: var(--border-width) solid transparent;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 10px;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }
        .nav-link:hover, .nav-link.active {
            border-color: var(--border-color);
            background-color: var(--secondary-color);
            box-shadow: 3px 3px 0px var(--border-color);
            transform: translate(-2px, -2px);
        }

        /* Card neo brutalism */
        .n-card {
            background-color: var(--card-bg);
            border: var(--border-width) solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--border-color);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        /* Buttons */
        .btn-neo {
            font-weight: 700;
            border: var(--border-width) solid var(--border-color);
            border-radius: 8px;
            box-shadow: 4px 4px 0px var(--border-color);
            transition: all 0.15s ease;
            text-transform: uppercase;
            padding: 8px 16px;
            color: #000;
        }
        .btn-neo:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0px var(--border-color);
            color: #000;
        }
        .btn-neo:active {
            transform: translate(4px, 4px);
            box-shadow: 0px 0px 0px var(--border-color);
        }
        .btn-neo-primary { background-color: var(--primary-color); }
        .btn-neo-success { background-color: var(--success-color); }
        .btn-neo-secondary { background-color: #fff; }
        .btn-neo-danger { background-color: #ff5252; }

        /* Tables */
        .table-neo {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: var(--border-width) solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--border-color);
        }
        .table-neo th, .table-neo td {
            border-bottom: var(--border-width) solid var(--border-color);
            border-right: var(--border-width) solid var(--border-color);
            padding: 12px 15px;
            font-weight: 600;
        }
        .table-neo th:last-child, .table-neo td:last-child {
            border-right: none;
        }
        .table-neo tr:last-child td {
            border-bottom: none;
        }
        .table-neo th {
            background-color: #e0e0e0;
            text-transform: uppercase;
        }
        
        /* Badges */
        .badge-neo {
            border: 2px solid var(--border-color);
            border-radius: 4px;
            padding: 4px 8px;
            font-weight: bold;
            box-shadow: 2px 2px 0px var(--border-color);
            color: #000;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        .badge-positif { background-color: var(--success-color); }
        .badge-negatif { background-color: #ff5252; }
        .badge-netral { background-color: #b0bec5; }
        .badge-pending { background-color: var(--secondary-color); }

        @media (max-width: 767.98px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: var(--border-width) solid var(--border-color);
                padding: 1rem;
            }
            .sidebar .nav {
                flex-direction: row !important;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .sidebar .nav-link {
                margin-bottom: 0;
                padding: 8px 10px;
                font-size: 0.85rem;
            }
            .sidebar .mt-auto {
                margin-top: 1rem !important;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .sidebar .mt-auto > div {
                margin-bottom: 0 !important;
                flex: 1;
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
                overflow-x: auto;
            }
            .table-neo {
                font-size: 0.85rem;
            }
            .table-neo th, .table-neo td {
                padding: 8px 10px;
                word-break: break-word;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4 class="mb-4 text-center">Dashboard Peneliti</h4>
    <nav class="nav flex-column">
        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="index.php">Overview</a>
        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'relevance.php') ? 'active' : '' ?>" href="relevance.php">Cek Relevansi</a>
        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'validation.php') ? 'active' : '' ?>" href="validation.php">Validasi Label</a>
        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dataset.php') ? 'active' : '' ?>" href="#">Dataset Final</a>
        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'model.php') ? 'active' : '' ?>" href="model.php">Training Model</a>
    </nav>
    <div class="mt-auto">
        <div class="p-3 border border-dark rounded mb-3 bg-light font-weight-bold" style="border-width: 2px !important;">
            👤 <?= htmlspecialchars($_SESSION['admin_username']) ?>
        </div>
        <a href="logout.php" class="btn btn-neo btn-neo-danger w-100">Logout</a>
    </div>
</div>

<!-- Main Content wrapper -->
<div class="main-content">
