<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

$error_msg = "";
$success_msg = "";

if (isset($_POST['register'])) {
  $nama = trim($_POST['nama']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $confirm_password = trim($_POST['confirm_password']);

  if (empty($nama) || empty($email) || empty($password) || empty($confirm_password)) {
    $error_msg = "Harap isi semua kolom pendaftaran!";
  } else if ($password !== $confirm_password) {
    $error_msg = "Konfirmasi kata sandi tidak cocok!";
  } else if (strlen($password) < 6) {
    $error_msg = "Kata sandi harus minimal 6 karakter!";
  } else {
    try {
      $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $error_msg = "Alamat email sudah digunakan oleh barista lain!";
      } else {
        // Insert new user (default role: barista)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'barista')");
        $stmt->execute([$nama, $email, $hashed_password]);
        
        $success_msg = "Pendaftaran berhasil! Silakan login menggunakan akun Anda.";
      }
    } catch (PDOException $e) {
      $error_msg = "Terjadi kesalahan database: " . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Barista — Sip & Serve</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh; padding: 2rem 1rem;">
  <div class="container" style="max-width: 550px;">
    <div class="retro-card shadow-lg p-4 p-md-5">
      <div class="text-center mb-4">
        <span class="logo-font fs-1">Sip & Serve <i class="bi bi-sparkles text-danger"></i></span>
        <h3 class="italic-serif mt-2 text-danger">Pendaftaran Barista</h3>
        <p class="text-muted small">Buat akun untuk mencatat dan meracik resep minuman premium</p>
      </div>

      <?php if (!empty($error_msg)): ?>
        <div class="alert alert-retro-danger text-center">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($success_msg)): ?>
        <div class="alert alert-retro-success text-center">
          <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_msg); ?>
          <script>
            setTimeout(function() {
              window.location.href = 'login.php';
            }, 2000);
          </script>
        </div>
      <?php endif; ?>

      <form method="POST" action="" class="needs-validation" novalidate>
        <div class="mb-3">
          <label for="nama" class="form-label">Nama Lengkap</label>
          <div class="input-group">
            <span class="input-group-text bg-transparent border-retro"><i class="bi bi-person text-danger"></i></span>
            <input type="text" name="nama" id="nama" class="form-control" placeholder="Nama lengkap Anda" required>
          </div>
          <div class="invalid-feedback">Harap isi nama lengkap!</div>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Alamat Email</label>
          <div class="input-group">
            <span class="input-group-text bg-transparent border-retro"><i class="bi bi-envelope text-danger"></i></span>
            <input type="email" name="email" id="email" class="form-control" placeholder="barista@sipserve.com" required>
          </div>
          <div class="invalid-feedback">Harap isi alamat email yang valid!</div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Kata Sandi</label>
          <div class="input-group">
            <span class="input-group-text bg-transparent border-retro"><i class="bi bi-lock text-danger"></i></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
          </div>
          <div class="invalid-feedback">Kata sandi minimal berisi 6 karakter!</div>
        </div>

        <div class="mb-4">
          <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi</label>
          <div class="input-group">
            <span class="input-group-text bg-transparent border-retro"><i class="bi bi-shield-lock text-danger"></i></span>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ketik ulang kata sandi" required minlength="6">
          </div>
          <div class="invalid-feedback">Harap konfirmasi kata sandi Anda!</div>
        </div>

        <button type="submit" name="register" class="btn btn-retro btn-lg w-100 py-2 mb-3">
          Daftar Sekarang <i class="bi bi-person-plus-fill"></i>
        </button>
      </form>
      
      <div class="text-center mt-2">
        <p class="text-muted small mb-0">Sudah punya akun? 
          <a href="login.php" class="text-danger fw-bold text-decoration-none">Masuk di Sini</a>
        </p>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/validation.js"></script>
</body>
</html>
