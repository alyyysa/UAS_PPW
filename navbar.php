<?php

require_once 'config.php';
check_login();

$active_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — Sip & Serve' : 'Sip & Serve'; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <!-- Custom Premium CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <nav class="navbar navbar-expand-lg retro-navbar">
      <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php" style="padding: 0;">
          <img src="assets/images/logo.png" alt="Sip & Serve" style="height: 48px; width: auto; object-fit: contain;">
        </a>
        <button class="navbar-toggler border-retro" type="button" data-bs-toggle="collapse" data-bs-target="#retroNavbar" aria-controls="retroNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="retroNavbar">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1 align-items-center">
            <li class="nav-item">
              <a class="nav-link <?php echo ($active_page == 'index.php' || $active_page == 'resep.php' || $active_page == 'resep_crud.php') ? 'active' : ''; ?>" href="index.php">
                <i class="bi bi-book-half me-1"></i> Resep
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo ($active_page == 'bahan.php') ? 'active' : ''; ?>" href="bahan.php">
                <i class="bi bi-egg-fried me-1"></i> Bahan
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo ($active_page == 'kategori.php') ? 'active' : ''; ?>" href="kategori.php">
                <i class="bi bi-tags-fill me-1"></i> Kategori
              </a>
            </li>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
              <li class="nav-item">
                <a class="nav-link <?php echo ($active_page == 'log.php') ? 'active' : ''; ?>" href="log.php">
                  <i class="bi bi-clock-history me-1"></i> Log Perubahan
                </a>
              </li>
            <?php endif; ?>
          </ul>
          
          <div class="d-flex align-items-center flex-wrap gap-3 mt-3 mt-lg-0 justify-content-center">
            <span class="text-muted small">
              <i class="bi bi-person-circle text-danger me-1"></i> Halo, <strong><?php echo htmlspecialchars($_SESSION['user_nama'] ?? 'Barista'); ?></strong> 
              (<span class="badge bg-danger rounded-pill" style="font-size: 0.7rem;"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'barista'); ?></span>)
            </span>
            <a href="logout.php" class="btn btn-retro btn-sm px-3"><i class="bi bi-box-arrow-right"></i> Keluar</a>
          </div>
        </div>
      </div>
    </nav>

    <?php if (isset($_SESSION['flash_success'])): ?>
      <div class="alert alert-retro-success alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
          <i class="bi bi-check-circle-fill me-2 fs-5"></i>
          <div><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
      <div class="alert alert-retro-danger alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
          <div><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
