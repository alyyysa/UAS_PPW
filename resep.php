<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  $_SESSION['flash_error'] = "ID Resep tidak valid!";
  header("Location: index.php");
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'serve') {
  try {
    $stmt = $pdo->prepare("
      SELECT rb.bahan_id, rb.jumlah, b.nama, b.stok, b.satuan, 
             cek_stok_cukup(b.id, rb.jumlah) AS cek_stok
      FROM resep_bahan rb
      JOIN bahan b ON rb.bahan_id = b.id
      WHERE rb.resep_id = ?
    ");
    $stmt->execute([$id]);
    $ingredients = $stmt->fetchAll();
    
    if (count($ingredients) === 0) {
      $_SESSION['flash_error'] = "Resep ini tidak memiliki bahan baku yang terkonfigurasi!";
      header("Location: resep.php?id=$id");
      exit;
    }
    
    $insufficient = [];
    foreach ($ingredients as $ing) {
      if (!$ing['cek_stok']) {
        $insufficient[] = sprintf(
          "%s (Dibutuhkan: %.1f %s, Tersedia: %.1f %s)",
          $ing['nama'], $ing['jumlah'], $ing['satuan'], $ing['stok'], $ing['satuan']
        );
      }
    }
    
    if (count($insufficient) > 0) {
      $_SESSION['flash_error'] = "Gagal membuat minuman! Stok bahan tidak mencukupi:<br>• " . implode("<br>• ", $insufficient);
      header("Location: resep.php?id=$id");
      exit;
    }
    
    $pdo->beginTransaction();
    
    $updateStmt = $pdo->prepare("UPDATE bahan SET stok = stok - ? WHERE id = ?");
    foreach ($ingredients as $ing) {
      $updateStmt->execute([$ing['jumlah'], $ing['bahan_id']]);
    }
    
    $pdo->commit();
    
    $stmt_name = $pdo->prepare("SELECT nama FROM resep WHERE id = ?");
    $stmt_name->execute([$id]);
    $recipe_name = $stmt_name->fetchColumn();
    
    $_SESSION['flash_success'] = "Satu porsi <strong>$recipe_name</strong> berhasil dibuat! Persediaan stok bahan otomatis dikurangi.";
    header("Location: resep.php?id=$id");
    exit;
    
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $_SESSION['flash_error'] = "Gagal melakukan transaksi serve resep: " . $e->getMessage();
    header("Location: resep.php?id=$id");
    exit;
  }
}

try {
  $stmt = $pdo->prepare("SELECT * FROM view_resep_lengkap WHERE id = ?");
  $stmt->execute([$id]);
  $resep = $stmt->fetch();
  
  if (!$resep) {
    $_SESSION['flash_error'] = "Resep tidak ditemukan!";
    header("Location: index.php");
    exit;
  }
  
  $stmt_cost = $pdo->prepare("SELECT hitung_biaya_resep(?)");
  $stmt_cost->execute([$id]);
  $biaya_resep = $stmt_cost->fetchColumn();
  
  $stmt_ing = $pdo->prepare("
    SELECT rb.jumlah, rb.catatan, b.nama AS nama_bahan, b.satuan, b.harga_per_unit, b.stok
    FROM resep_bahan rb
    JOIN bahan b ON rb.bahan_id = b.id
    WHERE rb.resep_id = ?
    ORDER BY b.nama ASC
  ");
  $stmt_ing->execute([$id]);
  $bahan_list = $stmt_ing->fetchAll();
  
} catch (PDOException $e) {
  die("Kesalahan database: " . $e->getMessage());
}

$page_title = $resep['nama_resep'];
require_once 'navbar.php';
?>

<div class="mb-4">
  <a href="index.php" class="btn btn-retro-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Katalog</a>
</div>

<div class="row g-4 mb-5">
  <div class="col-lg-8">
    <div class="retro-card h-100 py-4" style="background-color: var(--color-pastel-yellow);">
      <div class="mb-2">
        <span class="badge bg-danger rounded-pill px-3 py-1 text-uppercase fw-bold"><?php echo htmlspecialchars($resep['kategori']); ?></span>
      </div>
      <h1 class="display-4 italic-serif text-danger mb-3"><?php echo htmlspecialchars($resep['nama_resep']); ?></h1>
      <p class="fs-5 text-muted mb-4"><?php echo htmlspecialchars($resep['deskripsi'] ?: 'Tidak ada deskripsi.'); ?></p>
      
      <div class="border-retro-bottom my-3"></div>
      
      <div class="d-flex flex-wrap gap-4 text-muted">
        <div>
          <i class="bi bi-clock-fill text-danger me-1"></i> Waktu Buat: <strong><?php echo $resep['waktu_buat']; ?> menit</strong>
        </div>
        <div>
          <i class="bi bi-bar-chart-fill text-danger me-1"></i> Kesulitan: <strong class="text-uppercase"><?php echo htmlspecialchars($resep['tingkat_kesulitan']); ?></strong>
        </div>
        <div>
          <i class="bi bi-person-fill text-danger me-1"></i> Pembuat: <strong><?php echo htmlspecialchars($resep['dibuat_oleh'] ?: 'Sistem'); ?></strong>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-4">
    <div class="retro-card text-center h-100 d-flex flex-column align-items-center justify-content-center py-4">
      <div class="text-muted text-uppercase fw-bold small mb-1" style="letter-spacing: 0.5px;">Estimasi Biaya Bahan</div>
      <div class="display-font text-danger fw-extrabold mb-3" style="font-size: 2.5rem;">
        Rp<?php echo number_format($biaya_resep, 0, ',', '.'); ?>
      </div>
      
      <a href="resep.php?id=<?php echo $resep['id']; ?>&action=serve" class="btn btn-retro btn-lg w-100 py-3 mb-2">
        <i class="bi bi-cup-hot-fill"></i> Sajikan Minuman <br>
        <span class="small" style="font-size: 0.75rem; font-weight: normal;">(Potong Stok Bahan)</span>
      </a>
      <small class="text-muted text-center px-2">Mengurangi stok bahan baku sesuai dengan porsi resep.</small>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-5">
    <div class="retro-card h-100">
      <h3 class="italic-serif text-danger mb-4"><i class="bi bi-cart-check-fill"></i> Bahan-Bahan Baku</h3>
      
      <div class="table-responsive">
        <table class="table table-retro table-hover align-middle" style="font-size: 0.9rem;">
          <thead>
            <tr>
              <th>Bahan</th>
              <th class="text-end">Jumlah</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bahan_list as $bahan): ?>
              <?php 
                $stok_cukup = ($bahan['stok'] >= $bahan['jumlah']);
                $row_class = $stok_cukup ? '' : 'table-danger text-decoration-line-through';
              ?>
              <tr class="<?php echo $row_class; ?>">
                <td>
                  <strong><?php echo htmlspecialchars($bahan['nama_bahan']); ?></strong>
                  <?php if (!$stok_cukup): ?>
                    <span class="text-danger d-block small"><i class="bi bi-exclamation-circle-fill"></i> Habis (Stok: <?php echo floatval($bahan['stok']); ?>)</span>
                  <?php endif; ?>
                </td>
                <td class="text-end font-monospace">
                  <?php echo floatval($bahan['jumlah']); ?> <?php echo htmlspecialchars($bahan['satuan']); ?>
                </td>
                <td class="text-muted small"><?php echo htmlspecialchars($bahan['catatan'] ?: '-'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="col-md-7">
    <div class="retro-card h-100">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="italic-serif text-danger mb-0"><i class="bi bi-journal-text"></i> Langkah Pembuatan</h3>
        <div class="btn-group gap-2">
          <a href="resep_crud.php?action=edit&id=<?php echo $resep['id']; ?>" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
            <i class="bi bi-pencil-square"></i> Edit Resep
          </a>
          <a href="resep_crud.php?action=delete&id=<?php echo $resep['id']; ?>" 
             class="btn btn-retro bg-danger border-danger btn-sm px-3 rounded-pill btn-delete-confirm"
             data-action="menghapus resep minuman '<?php echo htmlspecialchars($resep['nama_resep']); ?>'">
            <i class="bi bi-trash-fill"></i> Hapus
          </a>
        </div>
      </div>
      
      <div class="fs-5 text-muted px-2" style="white-space: pre-line; line-height: 1.8;">
        <?php echo htmlspecialchars($resep['langkah'] ?: 'Langkah-langkah pembuatan belum didefinisikan.'); ?>
      </div>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
