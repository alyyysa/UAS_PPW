<?php

$page_title = "Manajemen Kategori";
require_once 'navbar.php';

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
      $nama = trim($_POST['nama']);
      $tipe = $_POST['tipe'];
      $deskripsi = trim($_POST['deskripsi']);
      
      $valid_types = ['cocktail','mocktail','coffee','tea','smoothie'];
      if (empty($nama) || !in_array($tipe, $valid_types)) {
        $error_msg = "Harap isi nama kategori dan pilih tipe yang valid!";
      } else {
        try {
          $stmt = $pdo->prepare("INSERT INTO kategori (nama, tipe, deskripsi) VALUES (?, ?, ?)");
          $stmt->execute([$nama, $tipe, $deskripsi]);
          $_SESSION['flash_success'] = "Kategori '$nama' berhasil ditambahkan!";
          header("Location: kategori.php");
          exit;
        } catch (PDOException $e) {
          $error_msg = "Gagal menambahkan kategori: " . $e->getMessage();
        }
      }
    }
    
    if ($action === 'edit') {
      $id = intval($_POST['id']);
      $nama = trim($_POST['nama']);
      $tipe = $_POST['tipe'];
      $deskripsi = trim($_POST['deskripsi']);
      
      $valid_types = ['cocktail','mocktail','coffee','tea','smoothie'];
      if ($id <= 0 || empty($nama) || !in_array($tipe, $valid_types)) {
        $error_msg = "Harap isi nama kategori dan pilih tipe yang valid!";
      } else {
        try {
          $stmt = $pdo->prepare("UPDATE kategori SET nama = ?, tipe = ?, deskripsi = ? WHERE id = ?");
          $stmt->execute([$nama, $tipe, $deskripsi, $id]);
          $_SESSION['flash_success'] = "Kategori '$nama' berhasil diperbarui!";
          header("Location: kategori.php");
          exit;
        } catch (PDOException $e) {
          $error_msg = "Gagal memperbarui kategori: " . $e->getMessage();
        }
      }
    }
  }
}

if (isset($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);
  try {
    // Check if category exists
    $stmt = $pdo->prepare("SELECT nama FROM kategori WHERE id = ?");
    $stmt->execute([$delete_id]);
    $kat_name = $stmt->fetchColumn();
    
    if ($kat_name) {
      $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
      $stmt->execute([$delete_id]);
      $_SESSION['flash_success'] = "Kategori '$kat_name' berhasil dihapus!";
    }
    header("Location: kategori.php");
    exit;
  } catch (PDOException $e) {
    // Catch foreign key integrity constraint violations
    if ($e->getCode() == 23000) {
      $_SESSION['flash_error'] = "Kategori tidak dapat dihapus karena masih digunakan di dalam salah satu resep minuman!";
    } else {
      $_SESSION['flash_error'] = "Gagal menghapus kategori: " . $e->getMessage();
    }
    header("Location: kategori.php");
    exit;
  }
}

try {
  $kategori_list = $pdo->query("
    SELECT k.*, COUNT(r.id) AS jumlah_resep 
    FROM kategori k
    LEFT JOIN resep r ON r.kategori_id = k.id
    GROUP BY k.id, k.nama, k.tipe, k.deskripsi, k.created_at
    ORDER BY k.nama ASC
  ")->fetchAll();
} catch (PDOException $e) {
  die("Gagal memuat kategori: " . $e->getMessage());
}
?>

<div class="retro-card">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="card-title-retro text-danger italic-serif mb-1">Daftar Kategori Minuman</h2>
      <p class="text-muted mb-0">Kelola kategori pengelompokkan menu minuman bar Anda.</p>
    </div>
    <div>
      <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
        <button class="btn btn-retro" data-bs-toggle="modal" data-bs-target="#addKategoriModal">
          <i class="bi bi-plus-circle-fill"></i> Tambah Kategori
        </button>
      <?php else: ?>
        <span class="badge bg-secondary rounded-pill px-3 py-2 text-muted" style="border: 1px dashed rgba(182, 54, 41, 0.3);">
          <i class="bi bi-lock-fill"></i> Hanya Admin yang Dapat Mengelola Kategori
        </span>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($error_msg)): ?>
    <div class="alert alert-retro-danger mt-3">
      <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
  <?php endif; ?>

  <div class="border-retro-bottom my-4"></div>

  <div class="table-responsive">
    <table class="table table-hover table-retro align-middle">
      <thead>
        <tr>
          <th>Nama Kategori</th>
          <th class="text-center">Tipe Minuman</th>
          <th>Deskripsi</th>
          <th class="text-center" style="width: 150px;">Jumlah Resep</th>
          <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <th class="text-center" style="width: 180px;">Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (count($kategori_list) > 0): ?>
          <?php foreach ($kategori_list as $kat): ?>
            <tr>
              <td>
                <strong class="fs-5 text-danger"><?php echo htmlspecialchars($kat['nama']); ?></strong>
              </td>
              <td class="text-center">
                <span class="badge bg-danger rounded-pill text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                  <?php echo htmlspecialchars($kat['tipe']); ?>
                </span>
              </td>
              <td>
                <span class="text-muted small"><?php echo htmlspecialchars($kat['deskripsi'] ?: '-'); ?></span>
              </td>
              <td class="text-center font-monospace fw-bold">
                <?php echo $kat['jumlah_resep']; ?> resep
              </td>
              <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <td class="text-center">
                  <div class="btn-group gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 btn-edit-kat" 
                            data-id="<?php echo $kat['id']; ?>"
                            data-nama="<?php echo htmlspecialchars($kat['nama']); ?>"
                            data-tipe="<?php echo htmlspecialchars($kat['tipe']); ?>"
                            data-deskripsi="<?php echo htmlspecialchars($kat['deskripsi']); ?>">
                      <i class="bi bi-pencil-fill"></i> Edit
                    </button>
                    <a href="kategori.php?delete_id=<?php echo $kat['id']; ?>" 
                       class="btn btn-retro bg-danger border-danger btn-sm px-3 rounded-pill btn-delete-confirm"
                       data-action="menghapus kategori '<?php echo htmlspecialchars($kat['nama']); ?>'">
                      <i class="bi bi-trash-fill"></i> Hapus
                    </a>
                  </div>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center py-4 text-muted">
              Tidak ada kategori yang terdaftar.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>

<div class="modal fade" id="addKategoriModal" tabindex="-1" aria-labelledby="addKategoriModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title italic-serif text-danger" id="addKategoriModalLabel">Tambah Kategori Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="" class="needs-validation" novalidate>
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          
          <div class="mb-3">
            <label for="nama" class="form-label">Nama Kategori</label>
            <input type="text" name="nama" class="form-control" placeholder="Contoh: Coffee & Latte, Sweet Mocktails" required>
            <div class="invalid-feedback">Nama kategori wajib diisi!</div>
          </div>
          
          <div class="mb-3">
            <label for="tipe" class="form-label">Tipe Minuman</label>
            <select name="tipe" class="form-select" required>
              <option value="coffee">Coffee</option>
              <option value="mocktail">Mocktail</option>
              <option value="cocktail">Cocktail</option>
              <option value="tea">Tea</option>
              <option value="smoothie">Smoothie</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi singkat mengenai jenis kategori ini..."></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-retro-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-retro">Simpan Kategori</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editKategoriModal" tabindex="-1" aria-labelledby="editKategoriModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title italic-serif text-danger" id="editKategoriModalLabel">Edit Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="" class="needs-validation" novalidate>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit-id">
          
          <div class="mb-3">
            <label for="edit-nama" class="form-label">Nama Kategori</label>
            <input type="text" name="nama" id="edit-nama" class="form-control" required>
            <div class="invalid-feedback">Nama kategori wajib diisi!</div>
          </div>
          
          <div class="mb-3">
            <label for="edit-tipe" class="form-label">Tipe Minuman</label>
            <select name="tipe" id="edit-tipe" class="form-select" required>
              <option value="coffee">Coffee</option>
              <option value="mocktail">Mocktail</option>
              <option value="cocktail">Cocktail</option>
              <option value="tea">Tea</option>
              <option value="smoothie">Smoothie</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="edit-deskripsi" class="form-label">Deskripsi</label>
            <textarea name="deskripsi" id="edit-deskripsi" class="form-control" rows="3"></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-retro-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-retro">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const editButtons = document.querySelectorAll('.btn-edit-kat');
  const editModalEl = document.getElementById('editKategoriModal');
  const editModal = new bootstrap.Modal(editModalEl);
  
  editButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('edit-id').value = this.dataset.id;
      document.getElementById('edit-nama').value = this.dataset.nama;
      document.getElementById('edit-tipe').value = this.dataset.tipe;
      document.getElementById('edit-deskripsi').value = this.dataset.deskripsi;
      
      editModal.show();
    });
  });
});
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
