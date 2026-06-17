<?php
$page_title = "Manajemen Bahan Baku";
require_once 'navbar.php';

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
      $nama = trim($_POST['nama']);
      $satuan = $_POST['satuan'];
      $stok = floatval($_POST['stok']);
      $harga = floatval($_POST['harga_per_unit']);
      $kategori_bahan = trim($_POST['kategori_bahan']);
      
      if (empty($nama) || empty($satuan) || $stok < 0 || $harga < 0) {
        $error_msg = "Harap isi semua kolom dengan valid! Stok & harga tidak boleh bernilai negatif.";
      } else {
        try {
          $stmt = $pdo->prepare("INSERT INTO bahan (nama, satuan, stok, harga_per_unit, kategori_bahan) VALUES (?, ?, ?, ?, ?)");
          $stmt->execute([$nama, $satuan, $stok, $harga, $kategori_bahan]);
          $_SESSION['flash_success'] = "Bahan baku '$nama' berhasil ditambahkan!";
          header("Location: bahan.php");
          exit;
        } catch (PDOException $e) {
          $error_msg = "Gagal menambahkan bahan: " . $e->getMessage();
        }
      }
    }
    
    if ($action === 'edit') {
      $id = intval($_POST['id']);
      $nama = trim($_POST['nama']);
      $satuan = $_POST['satuan'];
      $stok = floatval($_POST['stok']);
      $harga = floatval($_POST['harga_per_unit']);
      $kategori_bahan = trim($_POST['kategori_bahan']);
      
      if ($id <= 0 || empty($nama) || empty($satuan) || $stok < 0 || $harga < 0) {
        $error_msg = "Harap isi semua kolom dengan valid! Stok & harga tidak boleh bernilai negatif.";
      } else {
        try {
          $stmt = $pdo->prepare("UPDATE bahan SET nama = ?, satuan = ?, stok = ?, harga_per_unit = ?, kategori_bahan = ? WHERE id = ?");
          $stmt->execute([$nama, $satuan, $stok, $harga, $kategori_bahan, $id]);
          $_SESSION['flash_success'] = "Bahan baku '$nama' berhasil diperbarui!";
          header("Location: bahan.php");
          exit;
        } catch (PDOException $e) {
          $error_msg = "Gagal memperbarui bahan: " . $e->getMessage();
        }
      }
    }
  }
}

if (isset($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);
  try {
    $stmt = $pdo->prepare("SELECT nama FROM bahan WHERE id = ?");
    $stmt->execute([$delete_id]);
    $bahan_name = $stmt->fetchColumn();
    
    if ($bahan_name) {
      $stmt = $pdo->prepare("DELETE FROM bahan WHERE id = ?");
      $stmt->execute([$delete_id]);
      $_SESSION['flash_success'] = "Bahan baku '$bahan_name' berhasil dihapus!";
    }
    header("Location: bahan.php");
    exit;
  } catch (PDOException $e) {

  if ($e->getCode() == 23000) {
      $_SESSION['flash_error'] = "Bahan baku tidak dapat dihapus karena masih digunakan di dalam salah satu resep!";
    } else {
      $_SESSION['flash_error'] = "Gagal menghapus bahan baku: " . $e->getMessage();
    }
    header("Location: bahan.php");
    exit;
  }
}

try {
  $bahan_list = $pdo->query("SELECT * FROM view_stok_bahan ORDER BY nama ASC")->fetchAll();
} catch (PDOException $e) {
  die("Gagal memuat bahan baku: " . $e->getMessage());
}
?>

<div class="retro-card">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="card-title-retro text-danger italic-serif mb-1">Daftar Bahan Baku</h2>
      <p class="text-muted mb-0">Kelola stok dan harga satuan dari bahan-bahan mixology Anda.</p>
    </div>
    <div>
      <button class="btn btn-retro" data-bs-toggle="modal" data-bs-target="#addBahanModal">
        <i class="bi bi-plus-circle-fill"></i> Tambah Bahan Baku
      </button>
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
          <th>Nama Bahan</th>
          <th>Kategori Bahan</th>
          <th class="text-end">Stok Tersedia</th>
          <th class="text-center">Satuan</th>
          <th class="text-end">Harga/Unit</th>
          <th class="text-center">Status Stok</th>
          <th class="text-center" style="width: 180px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($bahan_list) > 0): ?>
          <?php foreach ($bahan_list as $bahan): ?>
            <tr>
              <td>
                <strong><?php echo htmlspecialchars($bahan['nama']); ?></strong>
              </td>
              <td>
                <span class="text-uppercase small text-muted"><?php echo htmlspecialchars($bahan['kategori_bahan'] ?: 'umum'); ?></span>
              </td>
              <td class="text-end font-monospace">
                <?php echo number_format($bahan['stok'], 2, ',', '.'); ?>
              </td>
              <td class="text-center">
                <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($bahan['satuan']); ?></span>
              </td>
              <td class="text-end font-monospace">
                Rp<?php echo number_format($bahan['harga_per_unit'], 0, ',', '.'); ?>
              </td>
              <td class="text-center">
                <?php if ($bahan['status_stok'] === 'aman'): ?>
                  <span class="badge badge-retro bg-success">Aman</span>
                <?php elseif ($bahan['status_stok'] === 'rendah'): ?>
                  <span class="badge badge-retro bg-warning">Rendah (&lt;50)</span>
                <?php else: ?>
                  <span class="badge badge-retro bg-danger">Habis</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="btn-group gap-2">
                  <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 btn-edit-bahan" 
                          data-id="<?php echo $bahan['id']; ?>"
                          data-nama="<?php echo htmlspecialchars($bahan['nama']); ?>"
                          data-satuan="<?php echo htmlspecialchars($bahan['satuan']); ?>"
                          data-stok="<?php echo $bahan['stok']; ?>"
                          data-harga="<?php echo $bahan['harga_per_unit']; ?>"
                          data-kategori="<?php echo htmlspecialchars($bahan['kategori_bahan']); ?>">
                    <i class="bi bi-pencil-fill"></i> Edit
                  </button>
                  <a href="bahan.php?delete_id=<?php echo $bahan['id']; ?>" 
                     class="btn btn-retro bg-danger border-danger btn-sm px-3 rounded-pill btn-delete-confirm"
                     data-action="menghapus bahan baku '<?php echo htmlspecialchars($bahan['nama']); ?>'">
                    <i class="bi bi-trash-fill"></i> Hapus
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              Tidak ada bahan baku yang terdaftar. Klik tombol di atas untuk menambah bahan baku baru.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addBahanModal" tabindex="-1" aria-labelledby="addBahanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title italic-serif text-danger" id="addBahanModalLabel">Tambah Bahan Baku Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="" id="ingredient-form" class="needs-validation" novalidate>
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          
          <div class="mb-3">
            <label for="nama" class="form-label">Nama Bahan Baku</label>
            <input type="text" name="nama" class="form-control" placeholder="Contoh: Susu Full Cream, Espresso" required>
            <div class="invalid-feedback">Nama bahan baku wajib diisi!</div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="satuan" class="form-label">Satuan</label>
              <select name="satuan" class="form-select" required>
                <option value="ml">ml (Mililiter)</option>
                <option value="gr">gr (Gram)</option>
                <option value="pcs">pcs (Butir/Potong)</option>
                <option value="sdm">sdm (Sendok Makan)</option>
                <option value="sdt">sdt (Sendok Teh)</option>
              </select>
            </div>
            
            <div class="col-md-6 mb-3">
              <label for="kategori_bahan" class="form-label">Kategori Bahan</label>
              <input type="text" name="kategori_bahan" class="form-control" placeholder="dairy, syrup, coffee, dll.">
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="stok" class="form-label">Stok Awal</label>
              <input type="number" step="0.01" name="stok" id="stok" class="form-control" placeholder="0.00" required min="0">
              <div class="invalid-feedback">Stok tidak boleh bernilai negatif!</div>
            </div>
            
            <div class="col-md-6 mb-3">
              <label for="harga_per_unit" class="form-label">Harga per Satuan (Rp)</label>
              <input type="number" step="0.01" name="harga_per_unit" id="harga_per_unit" class="form-control" placeholder="0" required min="0">
              <div class="invalid-feedback">Harga unit tidak boleh bernilai negatif!</div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-retro-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-retro">Simpan Bahan</button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal fade" id="editBahanModal" tabindex="-1" aria-labelledby="editBahanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title italic-serif text-danger" id="editBahanModalLabel">Edit Bahan Baku</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="" id="ingredient-edit-form" class="needs-validation" novalidate>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit-id">
          
          <div class="mb-3">
            <label for="edit-nama" class="form-label">Nama Bahan Baku</label>
            <input type="text" name="nama" id="edit-nama" class="form-control" required>
            <div class="invalid-feedback">Nama bahan baku wajib diisi!</div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit-satuan" class="form-label">Satuan</label>
              <select name="satuan" id="edit-satuan" class="form-select" required>
                <option value="ml">ml (Mililiter)</option>
                <option value="gr">gr (Gram)</option>
                <option value="pcs">pcs (Butir/Potong)</option>
                <option value="sdm">sdm (Sendok Makan)</option>
                <option value="sdt">sdt (Sendok Teh)</option>
              </select>
            </div>
            
            <div class="col-md-6 mb-3">
              <label for="edit-kategori" class="form-label">Kategori Bahan</label>
              <input type="text" name="kategori_bahan" id="edit-kategori" class="form-control">
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit-stok" class="form-label">Jumlah Stok</label>
              <input type="number" step="0.01" name="stok" id="edit-stok" class="form-control" required min="0">
              <div class="invalid-feedback">Stok tidak boleh bernilai negatif!</div>
            </div>
            
            <div class="col-md-6 mb-3">
              <label for="edit-harga" class="form-label">Harga per Satuan (Rp)</label>
              <input type="number" step="0.01" name="harga_per_unit" id="edit-harga" class="form-control" required min="0">
              <div class="invalid-feedback">Harga unit tidak boleh bernilai negatif!</div>
            </div>
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
  // Setup click listeners for populate edit ingredients modal
  const editButtons = document.querySelectorAll('.btn-edit-bahan');
  const editModalEl = document.getElementById('editBahanModal');
  const editModal = new bootstrap.Modal(editModalEl);
  
  editButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('edit-id').value = this.dataset.id;
      document.getElementById('edit-nama').value = this.dataset.nama;
      document.getElementById('edit-satuan').value = this.dataset.satuan;
      document.getElementById('edit-stok').value = this.dataset.stok;
      document.getElementById('edit-harga').value = this.dataset.harga;
      document.getElementById('edit-kategori').value = this.dataset.kategori;
      
      editModal.show();
    });
  });

  const editForm = document.getElementById('ingredient-edit-form');
  editForm.addEventListener('submit', function(event) {
    const stok = parseFloat(document.getElementById('edit-stok').value);
    const harga = parseFloat(document.getElementById('edit-harga').value);
    if (stok < 0 || harga < 0 || isNaN(stok) || isNaN(harga)) {
      event.preventDefault();
      event.stopPropagation();
      showToastAlert('Stok & Harga per unit tidak boleh bernilai negatif!', 'danger');
    }
  });
});
</script>

<?php require_once 'footer.php'; ?>
