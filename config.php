<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$db_host = '127.0.0.1';
$db_port = '3307';
$db_user = 'root';
$db_pass = '';
$db_name = 'sip_serve';

try {
  $pdo = new PDO("mysql:host=$db_host;port=$db_port;charset=utf8mb4", $db_user, $db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  
  $stmt = $pdo->query("SHOW DATABASES LIKE '$db_name'");
  $db_exists = $stmt->fetch();
  
  if (!$db_exists) {
    header("Location: setup.php");
    exit;
  }
  
  $pdo->query("USE `$db_name`");
  
  $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
  if (!$stmt->fetch()) {
    header("Location: setup.php");
    exit;
  }
  
} catch (PDOException $e) {
  if ($e->getCode() == 2002 || strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), '10061') !== false) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Koneksi Gagal — Sip & Serve</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="assets/css/style.css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    </head>
    <body class="d-flex align-items-center justify-content-center" style="min-height: 100vh; padding: 1rem;">
      <div class="container" style="max-width: 550px;">
        <div class="retro-card text-center py-5">
          <div class="mb-4">
            <span class="logo-font fs-1">Sip & Serve <i class="bi bi-sparkles text-danger"></i></span>
          </div>
          <div class="text-danger mb-4" style="font-size: 4rem;">
            <i class="bi bi-database-fill-slash"></i>
          </div>
          <h2 class="italic-serif text-danger mb-3">MySQL Server Offline</h2>
          <p class="mb-4 text-muted">
            Aplikasi Sip & Serve tidak dapat terhubung ke database. Harap nyalakan service <strong>MySQL</strong> di panel control <strong>XAMPP</strong> Anda terlebih dahulu!
          </p>
          <div class="alert alert-retro-danger text-start font-monospace small mb-4">
            Error: <?php echo htmlspecialchars($e->getMessage()); ?>
          </div>
          <a href="index.php" class="btn btn-retro"><i class="bi bi-arrow-clockwise"></i> Coba Hubungkan Kembali</a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
  } else {
    die("Koneksi Database Gagal: " . $e->getMessage());
  }
}

function check_login() {
  global $pdo;
  if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
  }
  
  if (!isset($_SESSION['user_role']) || !isset($_SESSION['user_nama'])) {
    try {
      $stmt = $pdo->prepare("SELECT nama, email, role FROM users WHERE id = ?");
      $stmt->execute([$_SESSION['user_id']]);
      $user = $stmt->fetch();
      if ($user) {
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
      } else {
        session_destroy();
        header("Location: login.php");
        exit;
      }
    } catch (PDOException $e) {
      $_SESSION['user_nama'] = $_SESSION['user_nama'] ?? 'Barista';
      $_SESSION['user_role'] = $_SESSION['user_role'] ?? 'barista';
    }
  }
}

function check_admin() {
  check_login();
  if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['flash_error'] = "Akses ditolak! Halaman ini memerlukan hak akses Admin.";
    header("Location: index.php");
    exit;
  }
}
?>
