<?php if(!defined('CONFIG_LOADED')){include_once 'config.php';define('CONFIG_LOADED',true);}?>
<?php include_once 'functions.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>css/style.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container py-2">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-file-search text-dark me-2" style="font-size: 1.8rem;"></i>
                <span class="fw-bold text-dark" style="font-size: 1.3rem; letter-spacing: 0.5px;">PAPERVISTA</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo isActivePage('index') ? 'active' : ''; ?>"
                           href="<?php echo SITE_URL; ?>">
                            Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo isActivePage('search') ? 'active' : ''; ?>"
                           href="<?php echo SITE_URL; ?>search.php">
                            Search Papers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo SITE_URL; ?>about.php">
                            About
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo isActivePage('dashboard') ? 'active' : ''; ?>"
                               href="<?php echo SITE_URL; ?>dashboard.php">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle ms-2" type="button"
                                    data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Profile
                                </a></li>
                                <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/">
                                    <i class="fas fa-user-shield me-2"></i>Admin Panel
                                </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-2">
                            <a href="<?php echo SITE_URL; ?>login.php" class="btn btn-outline-primary px-4">
                                LOGIN
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
