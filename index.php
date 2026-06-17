<?php

$page_title = "Katalog Resep";
require_once 'navbar.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori_id']) ? intval($_GET['kategori_id']) : 0;

try {
  $total_resep = $pdo->query("SELECT COUNT(*) FROM resep")->fetchColumn();
  $total_bahan = $pdo->query("SELECT COUNT(*) FROM bahan")->fetchColumn();
  $bahan_kritis = $pdo->query("SELECT COUNT(*) FROM bahan WHERE stok < 50")->fetchColumn();

  $kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama ASC")->fetchAll();

  $sneak_peek = $pdo->query("
    SELECT r.*, k.nama AS nama_kategori, k.tipe AS tipe_minuman, 
           hitung_biaya_resep(r.id) AS estimasi_biaya
    FROM resep r
    JOIN kategori k ON r.kategori_id = k.id
    ORDER BY r.created_at DESC
    LIMIT 3
  ")->fetchAll();

  $sql = "SELECT r.*, k.nama AS nama_kategori, k.tipe AS tipe_minuman, 
                 hitung_biaya_resep(r.id) AS estimasi_biaya, u.nama AS pembuat
          FROM resep r
          JOIN kategori k ON r.kategori_id = k.id
          LEFT JOIN users u ON r.created_by = u.id
          WHERE 1=1";
  
  $params = [];
  if (!empty($search)) {
    $sql .= " AND (r.nama LIKE ? OR r.deskripsi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
  }
  
  if ($kategori_filter > 0) {
    $sql .= " AND r.kategori_id = ?";
    $params[] = $kategori_filter;
  }
  
  $sql .= " ORDER BY r.created_at DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $resep_list = $stmt->fetchAll();

} catch (PDOException $e) {
  die("Kesalahan mengambil data katalog: " . $e->getMessage());
}

function get_pastel_class($tipe) {
  switch ($tipe) {
    case 'coffee': return 'pastel-yellow';
    case 'cocktail': return 'pastel-pink';
    case 'mocktail': return 'pastel-blue';
    case 'tea': return 'pastel-green';
    case 'smoothie': return 'pastel-purple';
    default: return 'pastel-blue';
  }
}

function render_drink_svg($tipe) {
  switch ($tipe) {
    case 'coffee':
      return '
      <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#b63629" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 8h1a4 4 0 1 1 0 8h-1" />
        <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z" />
        <line x1="6" y1="2" x2="6" y2="4" />
        <line x1="10" y1="2" x2="10" y2="4" />
        <line x1="14" y1="2" x2="14" y2="4" />
      </svg>';
    case 'cocktail':
      return '
      <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#b63629" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 3h18l-9 9z" />
        <path d="M12 12v9" />
        <path d="M8 21h8" />
        <circle cx="12" cy="7" r="1" fill="#b63629" />
      </svg>';
    case 'mocktail':
      return '
      <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#b63629" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 22h14" />
        <path d="M5 2h14v4L12 14L5 6V2z" />
        <path d="M12 14v8" />
        <path d="M16 5l-8 4" />
      </svg>';
    case 'tea':
      return '
      <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#b63629" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 8h1a3 3 0 0 1 0 6h-1" />
        <path d="M2 8h16v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V8z" />
        <path d="M2 22h16" />
        <path d="M8 2v2" />
        <path d="M12 2v2" />
      </svg>';
    case 'smoothie':
      return '
      <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#b63629" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 2h14v2H5z" />
        <path d="M6 4h12l-2 15a3 3 0 0 1-3 3h-2a3 3 0 0 1-3-3L6 4z" />
        <line x1="15" y1="1" x2="11" y2="8" />
      </svg>';
    default:
      return '
      <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#b63629" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="16" />
        <line x1="8" y1="12" x2="12" y2="12" />
      </svg>';
  }
}
?>

<div class="row g-4 align-items-stretch mb-5">
  <div class="col-md-7 d-flex">
    <div class="retro-card flex-fill d-flex flex-column justify-content-center" style="background-color: var(--color-pastel-pink);">
      <div class="mb-2">
        <span class="badge bg-danger rounded-pill px-3 py-2 text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Sip & Serve Cafe</span>
      </div>
      <h1 class="display-3 italic-serif text-danger mb-3" style="line-height: 1.1;">Rayakan Setiap Tegukan.</h1>
      <p class="fs-5 text-muted mb-4">
        Sip & Serve adalah platform manajemen resep minuman dan inventaris stok bahan baku kafe secara real-time. Membantu barista meracik resep terbaik, memantau ketersediaan bahan, serta menghitung estimasi biaya produksi secara otomatis.
      </p>
      <div class="d-flex flex-wrap gap-2">
        <a href="resep_crud.php?action=add" class="btn btn-retro"><i class="bi bi-plus-circle-fill"></i> Racik Resep Baru</a>
        <a href="bahan.php" class="btn btn-retro-secondary"><i class="bi bi-egg-fried"></i> Kelola Stok Bahan</a>
      </div>
    </div>
  </div>
  
  <div class="col-md-5 d-flex">
    <div class="retro-card flex-fill d-flex flex-column justify-content-between p-4" style="border-color: var(--color-primary); background-color: var(--bg-card);">
      <div>
        <div class="text-center mb-3">
          <img src="assets/images/logo.png" alt="Sip & Serve Logo" style="height: 55px; width: auto; object-fit: contain; margin-bottom: 0.5rem;">
          <h4 class="italic-serif text-danger mt-1 mb-1" style="font-size: 1.45rem;">Racikan Terbaru</h4>
          <p class="text-muted small mb-3">Intip menu terbaru yang baru saja diracik oleh barista.</p>
        </div>
        
        <div class="d-flex flex-column gap-3">
          <?php if (count($sneak_peek) > 0): ?>
            <?php foreach ($sneak_peek as $item): ?>
              <div class="d-flex align-items-center justify-content-between p-2 rounded-3 sneak-peek-item" style="background-color: white; border: 1.5px solid rgba(182, 54, 41, 0.15); transition: all 0.25s ease;">
                <div class="d-flex align-items-center gap-3">
                  <!-- Small Pastel Icon Badge -->
                  <div class="d-flex align-items-center justify-content-center rounded-circle <?php echo get_pastel_class($item['tipe_minuman']); ?>" style="width: 42px; height: 42px; border: 1.5px solid var(--color-primary); flex-shrink: 0;">
                    <?php 
                      switch ($item['tipe_minuman']) {
                        case 'coffee': echo '<i class="bi bi-cup-hot text-danger" style="font-size: 1.1rem;"></i>'; break;
                        case 'cocktail': echo '<i class="bi bi-glass-cocktail text-danger" style="font-size: 1.1rem;"></i>'; break;
                        case 'mocktail': echo '<i class="bi bi-cup-straw text-danger" style="font-size: 1.1rem;"></i>'; break;
                        case 'tea': echo '<i class="bi bi-cup text-danger" style="font-size: 1.1rem;"></i>'; break;
                        default: echo '<i class="bi bi-tropical-storm text-danger" style="font-size: 1.1rem;"></i>'; break;
                      }
                    ?>
                  </div>
                  <div class="text-start">
                    <h6 class="mb-0 fw-bold" style="font-size: 0.95rem; font-family: var(--font-sans);">
                      <a href="resep.php?id=<?php echo $item['id']; ?>" class="text-danger text-decoration-none hover-underline"><?php echo htmlspecialchars($item['nama']); ?></a>
                    </h6>
                    <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;"><?php echo htmlspecialchars($item['nama_kategori']); ?></span>
                  </div>
                </div>
                <div class="text-end">
                  <span class="badge bg-danger rounded-pill mb-1" style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['tingkat_kesulitan']); ?></span>
                  <div class="fw-bold text-danger font-monospace" style="font-size: 0.85rem;">Rp<?php echo number_format($item['estimasi_biaya'], 0, ',', '.'); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted small py-3">Belum ada resep yang diracik.</div>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="text-center mt-3 pt-2 border-top-retro-dashed">
        <a href="resep_crud.php?action=add" class="btn btn-retro btn-sm w-100 py-2"><i class="bi bi-plus-circle-fill"></i> Tambah Menu Baru</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-5">
  <div class="col-md-4">
    <div class="stats-card">
      <div class="stats-number"><?php echo $total_resep; ?></div>
      <div class="text-muted text-uppercase fw-bold small" style="letter-spacing: 0.5px;">Resep Minuman</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stats-card">
      <div class="stats-number"><?php echo $total_bahan; ?></div>
      <div class="text-muted text-uppercase fw-bold small" style="letter-spacing: 0.5px;">Bahan Baku Terdaftar</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stats-card">
      <div class="stats-number text-danger"><?php echo $bahan_kritis; ?></div>
      <div class="text-muted text-uppercase fw-bold small" style="letter-spacing: 0.5px;">Bahan Stok Rendah / Habis</div>
    </div>
  </div>
</div>

<div class="retro-card mb-5">
  <div class="row align-items-center g-3">
    <div class="col-lg-5">
      <h3 class="italic-serif text-danger mb-2 mb-lg-0">Pilihan Bestsellers & Resep</h3>
    </div>
    
    <div class="col-lg-7">
      <form method="GET" action="index.php" class="d-flex gap-2">
        <?php if ($kategori_filter > 0): ?>
          <input type="hidden" name="kategori_id" value="<?php echo $kategori_filter; ?>">
        <?php endif; ?>
        <input type="text" name="search" class="form-control" placeholder="Cari resep minuman..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-retro px-4"><i class="bi bi-search"></i> Cari</button>
        <?php if (!empty($search) || $kategori_filter > 0): ?>
          <a href="index.php" class="btn btn-retro-secondary px-3"><i class="bi bi-x-circle"></i> Reset</a>
        <?php endif; ?>
      </form>
    </div>
  </div>
  
  <div class="border-retro-bottom my-3"></div>
  
  <div class="d-flex flex-wrap gap-2">
    <a href="index.php?<?php echo !empty($search) ? 'search='.urlencode($search).'&' : ''; ?>kategori_id=0" 
       class="btn btn-sm <?php echo ($kategori_filter == 0) ? 'btn-retro' : 'btn-retro-secondary'; ?> rounded-pill px-4">
      Semua Minuman
    </a>
    <?php foreach ($kategori_list as $kat): ?>
      <a href="index.php?<?php echo !empty($search) ? 'search='.urlencode($search).'&' : ''; ?>kategori_id=<?php echo $kat['id']; ?>" 
         class="btn btn-sm <?php echo ($kategori_filter == $kat['id']) ? 'btn-retro' : 'btn-retro-secondary'; ?> rounded-pill px-4">
        <?php echo htmlspecialchars($kat['nama']); ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="row g-4">
  <?php if (count($resep_list) > 0): ?>
    <?php foreach ($resep_list as $resep): ?>
      <div class="col-md-6 col-lg-4 d-flex">
        <div class="product-card">
          <!-- Card Image / Header -->
          <div class="product-image-container <?php echo get_pastel_class($resep['tipe_minuman']); ?>">
            <span class="product-badge text-uppercase"><?php echo htmlspecialchars($resep['tingkat_kesulitan']); ?></span>
            <span class="product-heart"><i class="bi bi-heart-fill"></i></span>
            
            <?php if (!empty($resep['gambar']) && file_exists(__DIR__ . '/uploads/' . $resep['gambar'])): ?>
              <img src="uploads/<?php echo htmlspecialchars($resep['gambar']); ?>" alt="<?php echo htmlspecialchars($resep['nama']); ?>">
            <?php else: ?>
              <?php echo render_drink_svg($resep['tipe_minuman']); ?>
            <?php endif; ?>
          </div>
          
          <div>
            <div class="product-category"><?php echo htmlspecialchars($resep['nama_kategori']); ?></div>
            <h4 class="product-title"><?php echo htmlspecialchars($resep['nama']); ?></h4>
            <p class="text-muted small mb-3 text-truncate-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 40px;">
              <?php echo htmlspecialchars($resep['deskripsi'] ?: 'Tidak ada deskripsi.'); ?>
            </p>
          </div>
          
          <div class="product-footer">
            <div>
              <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Biaya Bahan</div>
              <div class="product-price"><?php echo 'Rp' . number_format($resep['estimasi_biaya'], 0, ',', '.'); ?></div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
              <a href="resep.php?id=<?php echo $resep['id']; ?>" class="btn btn-retro btn-sm px-3 rounded-pill" style="font-size: 0.8rem;">
                <i class="bi bi-eye"></i> Detail
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="col-12">
      <div class="retro-card text-center py-5">
        <i class="bi bi-search text-danger fs-1 mb-3"></i>
        <h4 class="italic-serif text-danger">Resep Tidak Ditemukan</h4>
        <p class="text-muted">Cobalah untuk mengubah kata kunci pencarian Anda atau tambahkan resep baru.</p>
        <a href="resep_crud.php?action=add" class="btn btn-retro mt-2"><i class="bi inline-plus-circle-fill"></i> Tambah Resep Baru</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
