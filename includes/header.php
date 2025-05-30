<?php
// Oturum kontrolü
requireLogin();

// Mevcut kullanıcı bilgilerini al
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
<!-- Mevcut stiller yerine -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo url('assets/images/favicon.ico'); ?>" type="image/x-icon">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-xl-2 px-0 bg-dark sidebar">
                <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-100">
                    <a href="<?php echo url('index.php'); ?>" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <img src="<?php echo url('assets/images/logo.png'); ?>" alt="Seozof Agency" height="40">
                    </a>
                    <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=dashboard'); ?>" class="nav-link text-white <?php echo isActiveMenu('dashboard'); ?>">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=clients'); ?>" class="nav-link text-white <?php echo isActiveMenu('clients'); ?>">
                                <i class="bi bi-people me-2"></i> Müşteriler
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=projects'); ?>" class="nav-link text-white <?php echo isActiveMenu('projects'); ?>">
                                <i class="bi bi-briefcase me-2"></i> Projeler
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=keywords'); ?>" class="nav-link text-white <?php echo isActiveMenu('keywords'); ?>">
                                <i class="bi bi-key me-2"></i> Anahtar Kelimeler
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=tasks'); ?>" class="nav-link text-white <?php echo isActiveMenu('tasks'); ?>">
                                <i class="bi bi-list-check me-2"></i> Görevler
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=content'); ?>" class="nav-link text-white <?php echo isActiveMenu('content'); ?>">
                                <i class="bi bi-file-earmark-text me-2"></i> İçerik Yönetimi
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=reports'); ?>" class="nav-link text-white <?php echo isActiveMenu('reports'); ?>">
                                <i class="bi bi-graph-up me-2"></i> Raporlar
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=ai-assistant'); ?>" class="nav-link text-white <?php echo isActiveMenu('ai-assistant'); ?>">
                                <i class="bi bi-robot me-2"></i> AI Asistan
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] == 'admin'): ?>
                        <li class="nav-item w-100">
                            <a href="<?php echo url('index.php?page=settings'); ?>" class="nav-link text-white <?php echo isActiveMenu('settings'); ?>">
                                <i class="bi bi-gear me-2"></i> Ayarlar
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <hr class="w-100">
                    <div class="dropdown pb-4 w-100">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-light text-dark d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-weight: bold;">
                                <?php echo substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1); ?>
                            </div>
                            <span class="d-none d-sm-inline mx-1"><?php echo $currentUser['first_name'] . ' ' . $currentUser['last_name']; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="<?php echo url('index.php?page=settings&action=profile'); ?>">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo url('logout.php'); ?>">Çıkış Yap</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-xl-10 py-3 px-4 content-area">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-light rounded mb-4">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav me-auto">
                                <li class="nav-item">
                                    <?php 
                                    $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
                                    $pageTitle = ucfirst(str_replace('-', ' ', $currentPage));
                                    echo '<span class="nav-link fw-bold">' . $pageTitle . '</span>'; 
                                    ?>
                                </li>
                            </ul>
                            <form class="d-flex">
                                <div class="input-group">
                                    <input class="form-control" type="search" placeholder="Ara..." aria-label="Ara">
                                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                                </div>
                            </form>
                            <ul class="navbar-nav ms-auto">
                                <li class="nav-item dropdown">
                                    <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-bell"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            3
                                        </span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                        <li><a class="dropdown-item" href="#">Yeni görev atandı</a></li>
                                        <li><a class="dropdown-item" href="#">Anahtar kelime sıralaması değişti</a></li>
                                        <li><a class="dropdown-item" href="#">Rapor hazır</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
                
                <!-- Flash Messages -->
                <?php showFlashMessages(); ?>
                
                <!-- Content -->
                <div class="content-container">