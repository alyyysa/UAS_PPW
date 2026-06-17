<?php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'add';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$error_msg = "";
$recipe_data = [
  'nama' => '',
  'kategori_id' => '',
  'deskripsi' => '',
  'langkah' => '',
  'waktu_buat' => 5,
  'tingkat_kesulitan' => 'mudah',
  'gambar' => ''
];
$recipe_ingredients = [];

if ($action === 'delete') {
  if ($id <= 0) {
    $_SESSION['flash_error'] = "ID Resep tidak valid!";
    header("Location: index.php");
    exit;
  }
  
  try {
    $stmt = $pdo->prepare("SELECT nama, gambar FROM resep WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    
    if ($res) {
      if (!empty($res['gambar']) && file_exists(__DIR__ . '/uploads/' . $res['gambar'])) {
        unlink(__DIR__ . '/uploads/' . $res['gambar']);
      }
      
      $stmt_del = $pdo->prepare("DELETE FROM resep WHERE id = ?");
      $stmt_del->execute([$id]);
      
      $_SESSION['flash_success'] = "Resep '" . $res['nama'] . "' berhasil dihapus!";
    }
    header("Location: index.php");
    exit;
  } catch (PDOException $e) {
    $_SESSION['flash_error'] = "Gagal menghapus resep: " . $e->getMessage();
    header("Location: index.php");
    exit;
  }
}

if ($action === 'edit') {
  if ($id <= 0) {
    $_SESSION['flash_error'] = "ID Resep tidak valid!";
    header("Location: index.php");
    exit;
  }
  
  try {
    $stmt = $pdo->prepare("SELECT * FROM resep WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    
    if ($res) {
      $recipe_data = $res;
      
      // Fetch recipe ingredients
      $stmt_ing = $pdo->prepare("SELECT bahan_id, jumlah, catatan FROM resep_bahan WHERE resep_id = ?");
      $stmt_ing->execute([$id]);
      $recipe_ingredients = $stmt_ing->fetchAll();
    } else {
      $_SESSION['flash_error'] = "Resep tidak ditemukan!";
      header("Location: index.php");
      exit;
    }
  } catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama']);
  $kategori_id = intval($_POST['kategori_id']);
  $deskripsi = trim($_POST['deskripsi']);
  $langkah = trim($_POST['langkah']);
  $waktu_buat = intval($_POST['waktu_buat']);
  $tingkat_kesulitan = $_POST['tingkat_kesulitan'];
  
  $bahan_ids = isset($_POST['bahan_id']) ? $_POST['bahan_id'] : [];
  $jumlahs = isset($_POST['jumlah']) ? $_POST['jumlah'] : [];
  $catatan_bahans = isset($_POST['catatan_bahan']) ? $_POST['catatan_bahan'] : [];

  if (empty($nama) || $kategori_id <= 0 || count($bahan_ids) === 0) {
    $error_msg = "Harap isi semua kolom wajib dan tambahkan minimal 1 bahan baku!";
  } else {
    try {
      $pdo->beginTransaction();
      
      $gambar_name = $recipe_data['gambar']; // keep current image name by default
      if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $file_name = $_FILES['gambar']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        if (!in_array($file_ext, $allowed_exts)) {
          throw new Exception("Format berkas gambar tidak didukung! Gunakan format JPG, PNG, WEBP, atau SVG.");
        }
        
        if (!is_dir(__DIR__ . '/uploads')) {
          mkdir(__DIR__ . '/uploads', 0777, true);
        }
        
        if (!empty($recipe_data['gambar']) && file_exists(__DIR__ . '/uploads/' . $recipe_data['gambar'])) {
          unlink(__DIR__ . '/uploads/' . $recipe_data['gambar']);
        }
        
        $gambar_name = 'img_' . uniqid() . '.' . $file_ext;
        move_uploaded_file($file_tmp, __DIR__ . '/uploads/' . $gambar_name);
      }
      
      if ($action === 'add') {
        $stmt = $pdo->prepare("
          INSERT INTO resep (nama, kategori_id, deskripsi, langkah, waktu_buat, tingkat_kesulitan, gambar, created_by)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
          $nama, $kategori_id, $deskripsi, $langkah, $waktu_buat, $tingkat_kesulitan, $gambar_name, $_SESSION['user_id']
        ]);
        $recipe_id = $pdo->lastInsertId();
      } else {
        $stmt = $pdo->prepare("
          UPDATE resep 
          SET nama = ?, kategori_id = ?, deskripsi = ?, langkah = ?, waktu_buat = ?, tingkat_kesulitan = ?, gambar = ?
          WHERE id = ?
        ");
        $stmt->execute([
          $nama, $kategori_id, $deskripsi, $langkah, $waktu_buat, $tingkat_kesulitan, $gambar_name, $id
        ]);
        $recipe_id = $id;
        
        $stmt_clear = $pdo->prepare("DELETE FROM resep_bahan WHERE resep_id = ?");
        $stmt_clear->execute([$recipe_id]);
      }
      
      $stmt_pivot = $pdo->prepare("INSERT INTO resep_bahan (resep_id, bahan_id, jumlah, catatan) VALUES (?, ?, ?, ?)");
      foreach ($bahan_ids as $idx => $b_id) {
        $b_id = intval($b_id);
        $qty = floatval($jumlahs[$idx]);
        $note = trim($catatan_bahans[$idx]);
        
        if ($b_id > 0 && $qty > 0) {
          $stmt_pivot->execute([$recipe_id, $b_id, $qty, empty($note) ? null : $note]);
        }
      }
      
      $pdo->commit();
      
      $_SESSION['flash_success'] = ($action === 'add') ? "Resep '$nama' berhasil diracik!" : "Resep '$nama' berhasil diperbarui!";
      header("Location: resep.php?id=" . $recipe_id);
      exit;
      
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $error_msg = "Kesalahan memproses resep: " . $e->getMessage();
    }
  }
}

try {
  $kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama ASC")->fetchAll();
  $all_ingredients = $pdo->query("SELECT id, nama, satuan, harga_per_unit, stok FROM bahan ORDER BY nama ASC")->fetchAll();
} catch (PDOException $e) {
  die("Database fetch error: " . $e->getMessage());
}

echo "<script>window.ALL_INGREDIENTS = " . json_encode($all_ingredients) . ";</script>";

$page_title = ($action === 'add') ? "Racik Resep Baru" : "Edit Resep: " . $recipe_data['nama'];
require_once 'navbar.php';
?>

<div class="mb-4">
  <a href="<?php echo ($action === 'edit') ? 'resep.php?id='.$id : 'index.php'; ?>" class="btn btn-retro-secondary">
    <i class="bi bi-arrow-left"></i> Batal / Kembali
  </a>
</div>

<div class="retro-card">
  <h2 class="card-title-retro italic-serif text-danger mb-4">
    <?php echo ($action === 'add') ? 'Racik Resep Minuman Baru' : 'Edit Formula Resep'; ?>
  </h2>
  
  <?php if (!empty($error_msg)): ?>
    <div class="alert alert-retro-danger">
      <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="" id="recipe-form" enctype="multipart/form-data" class="needs-validation" novalidate>
    <div class="row g-4">
      <!-- Recipe details left column -->
      <div class="col-md-6">
        <h4 class="italic-serif text-danger border-bottom-retro-bottom pb-2 mb-3"><i class="bi bi-info-circle"></i> Detail Menu</h4>
        
        <div class="mb-3">
          <label for="nama" class="form-label">Nama Minuman <span class="text-danger">*</span></label>
          <input type="text" name="nama" id="nama" class="form-control" placeholder="Contoh: Coffee Latte Caramel, Lemon Mint Cooler" value="<?php echo htmlspecialchars($recipe_data['nama']); ?>" required>
          <div class="invalid-feedback">Nama resep wajib diisi!</div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="kategori_id" class="form-label">Kategori <span class="text-danger">*</span></label>
            <select name="kategori_id" id="kategori_id" class="form-select" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($kategori_list as $kat): ?>
                <option value="<?php echo $kat['id']; ?>" <?php echo ($recipe_data['kategori_id'] == $kat['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($kat['nama']); ?> (<?php echo htmlspecialchars($kat['tipe']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Silakan pilih kategori minuman!</div>
          </div>
          
          <div class="col-md-6 mb-3">
            <label for="tingkat_kesulitan" class="form-label">Tingkat Kesulitan</label>
            <select name="tingkat_kesulitan" id="tingkat_kesulitan" class="form-select">
              <option value="mudah" <?php echo ($recipe_data['tingkat_kesulitan'] === 'mudah') ? 'selected' : ''; ?>>Mudah</option>
              <option value="sedang" <?php echo ($recipe_data['tingkat_kesulitan'] === 'sedang') ? 'selected' : ''; ?>>Sedang</option>
              <option value="sulit" <?php echo ($recipe_data['tingkat_kesulitan'] === 'sulit') ? 'selected' : ''; ?>>Sulit</option>
            </select>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="waktu_buat" class="form-label">Waktu Pembuatan (menit)</label>
            <input type="number" name="waktu_buat" id="waktu_buat" class="form-control" value="<?php echo htmlspecialchars($recipe_data['waktu_buat']); ?>" min="1" max="180">
          </div>
          
          <div class="col-md-6 mb-3">
            <label for="gambar" class="form-label">Foto Minuman (Opsional)</label>
            <input type="file" name="gambar" id="gambar" class="form-control">
            <?php if (!empty($recipe_data['gambar'])): ?>
              <small class="text-muted d-block mt-1">File saat ini: <code><?php echo htmlspecialchars($recipe_data['gambar']); ?></code></small>
            <?php endif; ?>
          </div>
        </div>

        <div class="mb-3">
          <label for="deskripsi" class="form-label">Deskripsi Singkat</label>
          <textarea name="deskripsi" id="deskripsi" class="form-control" rows="2" placeholder="Tuliskan catatan aroma, rasa, atau penyajian..."><?php echo htmlspecialchars($recipe_data['deskripsi']); ?></textarea>
        </div>

        <div class="mb-3">
          <label for="langkah" class="form-label">Langkah-Langkah Pembuatan</label>
          <textarea name="langkah" id="langkah" class="form-control" rows="4" placeholder="1. Seduh espresso...&#10;2. Steam susu...&#10;3. Tuang ke gelas..." required><?php echo htmlspecialchars($recipe_data['langkah']); ?></textarea>
          <div class="invalid-feedback">Langkah pembuatan resep harus ditulis!</div>
        </div>
      </div>
      
      <!-- Recipe ingredients right column -->
      <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center border-bottom-retro-bottom pb-2 mb-3">
          <h4 class="italic-serif text-danger mb-0"><i class="bi bi-egg-fried"></i> Konfigurasi Bahan</h4>
          <button type="button" class="btn btn-retro btn-sm py-1 px-3" onclick="addIngredientRow()">
            <i class="bi bi-plus"></i> Tambah Baris
          </button>
        </div>
        
        <div class="table-responsive mb-4">
          <table class="table align-middle" style="font-size: 0.85rem;">
            <thead>
              <tr>
                <th style="width: 40%;">Nama Bahan <span class="text-danger">*</span></th>
                <th style="width: 25%;">Takaran <span class="text-danger">*</span></th>
                <th style="width: 20%;">Catatan</th>
                <th style="width: 15%;">Biaya</th>
                <th class="text-center" style="width: 10%;">Aksi</th>
              </tr>
            </thead>
            <tbody id="ingredients-container">
              <!-- JS dynamic ingredients appended here -->
            </tbody>
          </table>
        </div>
        
        <div class="retro-card bg-cream text-center p-3 mb-4" style="border-style: dashed;">
          <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Estimasi Total Biaya Bahan</span>
          <h3 id="total-recipe-cost" class="display-font text-danger fw-bold mt-1 mb-0">Rp0</h3>
          <small class="text-muted text-center d-block mt-1">Dihitung otomatis berdasarkan harga per unit bahan.</small>
        </div>
      </div>
    </div>
    
    <div class="border-retro-bottom my-4"></div>
    
    <div class="text-end">
      <a href="<?php echo ($action === 'edit') ? 'resep.php?id='.$id : 'index.php'; ?>" class="btn btn-retro-secondary me-2">Batal</a>
      <button type="submit" class="btn btn-retro px-5 py-2">
        <i class="bi bi-save-fill"></i> Simpan Formula Resep
      </button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  <?php if (count($recipe_ingredients) > 0): ?>
    <?php foreach ($recipe_ingredients as $ri): ?>
      addIngredientRow(<?php echo $ri['bahan_id']; ?>, <?php echo $ri['jumlah']; ?>, '<?php echo addslashes($ri['catatan']); ?>');
    <?php endforeach; ?>
  <?php else: ?>
    addIngredientRow();
  <?php endif; ?>
});
</script>

<?php require_once 'footer.php'; ?>
