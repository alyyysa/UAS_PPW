<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$db_host = '127.0.0.1';
$db_port = '3307';
$db_user = 'root';
$db_pass = '';
$db_name = 'sip_serve';

$status_log = [];
$success = false;
$error_msg = "";

if (isset($_POST['install'])) {
  try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;charset=utf8mb4", $db_user, $db_pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $status_log[] = "Terhubung ke MySQL Server pada localhost.";

    $sql_file = __DIR__ . '/database.sql';
    if (!file_exists($sql_file)) {
      throw new Exception("File schema database.sql tidak ditemukan di direktori root!");
    }
    
    $sql_content = file_get_contents($sql_file);
    $status_log[] = "Membaca file database.sql (Size: " . strlen($sql_content) . " bytes).";

    $lines = explode("\n", $sql_content);
    $queries = [];
    $current_query = "";
    $delimiter = ";";

    foreach ($lines as $line) {
      $trimmed = trim($line);
      // Skip comments or blank lines
      if (empty($trimmed) || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0) {
        continue;
      }
      
      if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $matches)) {
        $delimiter = trim($matches[1]);
        continue;
      }
      
      $current_query .= "\n" . $line;
      
      if (substr($trimmed, -strlen($delimiter)) === $delimiter) {
        $query_to_run = substr(trim($current_query), 0, -strlen($delimiter));
        if (!empty(trim($query_to_run))) {
          $queries[] = trim($query_to_run);
        }
        $current_query = "";
      }
    }
    
    $status_log[] = "Berhasil mem-parsing " . count($queries) . " query SQL.";

    $db_created_or_used = false;
    foreach ($queries as $idx => $query) {
      if (stripos($query, 'DROP DATABASE') !== false || stripos($query, 'CREATE DATABASE') !== false || stripos($query, 'USE ') !== false) {
        $pdo->exec($query);
        $db_created_or_used = true;
      }
    }

    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $status_log[] = "Membuat database '$db_name' dan memproses tabel-tabel...";

    $table_count = 0;
    $trigger_count = 0;
    $func_count = 0;
    $view_count = 0;
    $seed_count = 0;

    foreach ($queries as $query) {
      $trimmed_query = trim($query);
      if (stripos($trimmed_query, 'DROP DATABASE') !== false || 
          stripos($trimmed_query, 'CREATE DATABASE') !== false || 
          stripos($trimmed_query, 'USE ') !== false ||
          strncasecmp($trimmed_query, 'SELECT', 6) === 0) {
        continue;
      }

      $pdo->exec($query);
      
      if (stripos($query, 'CREATE TABLE') !== false) {
        $table_count++;
      } else if (stripos($query, 'CREATE TRIGGER') !== false) {
        $trigger_count++;
      } else if (stripos($query, 'CREATE FUNCTION') !== false) {
        $func_count++;
      } else if (stripos($query, 'CREATE VIEW') !== false) {
        $view_count++;
      } else if (stripos($query, 'INSERT INTO') !== false) {
        $seed_count++;
      }
    }

    $status_log[] = "Tabel terbuat: $table_count, View terbuat: $view_count, Triggers: $trigger_count, Functions: $func_count.";

    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $barista_hash = password_hash('barista123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$admin_hash, 'admin@sipserve.com']);
    $stmt->execute([$barista_hash, 'budi@sipserve.com']);
    $stmt->execute([$barista_hash, 'sari@sipserve.com']);

    $status_log[] = "Password placeholder user berhasil di-hash menggunakan bcrypt.";
    $status_log[] = "Inisialisasi basis data selesai secara sukses!";
    $success = true;

  } catch (Exception $e) {
    $error_msg = $e->getMessage();
    $status_log[] = "ERROR: " . $error_msg;
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup Database — Sip & Serve</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="py-5">
  <div class="container" style="max-width: 700px;">
    <div class="retro-card shadow-lg">
      <div class="text-center mb-4">
        <span class="logo-font fs-1">Sip & Serve <i class="bi bi-sparkles text-danger"></i></span>
        <h3 class="italic-serif mt-2">Instalasi & Seeder Basis Data</h3>
      </div>

      <?php if (!empty($error_msg)): ?>
        <div class="alert alert-retro-danger">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <strong>Instalasi Gagal:</strong><br>
          <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-retro-success py-3 text-center mb-4">
          <i class="bi bi-check-circle-fill fs-3 d-block mb-2 text-success"></i>
          <strong>Database Sip & Serve Berhasil Dikonfigurasi!</strong><br>
          Semua tabel, view, function, trigger, dan seeder data telah terpasang.
        </div>
        
        <h4 class="italic-serif text-danger border-bottom-retro-bottom pb-2 mb-3">Akun Demo yang Siap Digunakan:</h4>
        <div class="table-responsive mb-4">
          <table class="table table-bordered table-retro">
            <thead>
              <tr>
                <th>Email</th>
                <th>Password</th>
                <th>Role</th>
                <th>Kegunaan</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>admin@sipserve.com</code></td>
                <td><code>admin123</code></td>
                <td><span class="badge bg-danger rounded-pill">admin</span></td>
                <td>Manajemen Kategori & Log Perubahan</td>
              </tr>
              <tr>
                <td><code>budi@sipserve.com</code></td>
                <td><code>barista123</code></td>
                <td><span class="badge bg-secondary rounded-pill">barista</span></td>
                <td>CRUD Resep + Bahan, Serve Resep</td>
              </tr>
              <tr>
                <td><code>sari@sipserve.com</code></td>
                <td><code>barista123</code></td>
                <td><span class="badge bg-secondary rounded-pill">barista</span></td>
                <td>CRUD Resep + Bahan, Serve Resep</td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="text-center mt-4">
          <a href="login.php" class="btn btn-retro btn-lg w-100">Lanjut ke Halaman Login <i class="bi bi-arrow-right"></i></a>
        </div>
      <?php else: ?>
        <p class="text-muted text-center mb-4">
          Halaman ini akan mengotomatisasi pembuatan database <strong>sip_serve</strong>, membuat semua tabel relasional, triggers, views, function <code>hitung_biaya_resep()</code>, dan meng-import data contoh dari file <code>database.sql</code>.
        </p>

        <form method="POST" action="">
          <button type="submit" name="install" class="btn btn-retro btn-lg w-100 mb-4 py-3">
            <i class="bi bi-play-circle-fill"></i> Mulai Inisialisasi Database
          </button>
        </form>
      <?php endif; ?>

      <?php if (!empty($status_log)): ?>
        <div class="mt-4">
          <h5 class="italic-serif">Log Proses Instalasi:</h5>
          <div class="bg-dark text-light p-3 rounded-3 font-monospace" style="font-size: 0.85rem; max-height: 200px; overflow-y: auto;">
            <?php foreach ($status_log as $log): ?>
              <div>&gt; <?php echo htmlspecialchars($log); ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
