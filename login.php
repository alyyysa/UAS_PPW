<?php

require_once 'config.php';

if (isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

$error_msg = "";
if (isset($_POST['login'])) {
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  if (empty($email) || empty($password)) {
    $error_msg = "Harap isi semua kolom email dan password!";
  } else {
    try {
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
      $stmt->execute([$email]);
      $user = $stmt->fetch();

      if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        header("Location: index.php");
        exit;
      } else {
        $error_msg = "Email atau password salah!";
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
  <title>Login — Sip & Serve</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh; padding: 1rem;">
  <div class="container" style="max-width: 500px;">
    <div class="retro-card shadow-lg p-4 p-md-5">
      <div class="text-center mb-4">
        <span class="logo-font fs-1">Sip & Serve <i class="bi bi-sparkles text-danger"></i></span>
        <h3 class="italic-serif mt-2 text-danger">Selamat Datang</h3>
        <p class="text-muted small">Silakan masuk untuk mengelola resep dan bahan minuman</p>
      </div>

      <?php if (!empty($error_msg)): ?>
        <div class="alert alert-retro-danger text-center">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" class="needs-validation" novalidate>
        <div class="mb-3">
          <label for="email" class="form-label">Alamat Email</label>
          <div class="input-group">
            <span class="input-group-text bg-transparent border-retro"><i class="bi bi-envelope text-danger"></i></span>
            <input type="email" name="email" id="email" class="form-control" placeholder="nama@sipserve.com" required>
          </div>
          <div class="invalid-feedback">Harap isi alamat email yang valid!</div>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label">Kata Sandi</label>
          <div class="input-group">
            <span class="input-group-text bg-transparent border-retro"><i class="bi bi-lock text-danger"></i></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan kata sandi" required>
          </div>
          <div class="invalid-feedback">Harap masukkan kata sandi!</div>
        </div>

        <button type="submit" name="login" class="btn btn-retro btn-lg w-100 py-2 mb-3">
          Masuk Sekarang <i class="bi bi-box-arrow-in-right"></i>
        </button>
      </form>
      
      <div class="text-center mt-3">
        <p class="text-muted small mb-0">Belum punya akun barista? 
          <a href="register.php" class="text-danger fw-bold text-decoration-none">Daftar Akun Baru</a>
        </p>
        <p class="text-muted small mt-2">
          <a href="setup.php" class="text-secondary text-decoration-none"><i class="bi bi-gear-fill"></i> Reset / Inisialisasi Database</a>
        </p>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/validation.js"></script>
</body>
</html>
