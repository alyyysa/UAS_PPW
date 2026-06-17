<?php

$page_title = "Log Aktivitas Resep";
require_once 'navbar.php';

check_admin();

try {
  $stmt = $pdo->query("
    SELECT lr.*, r.nama AS nama_resep
    FROM log_resep lr
    LEFT JOIN resep r ON lr.resep_id = r.id
    ORDER BY lr.diubah_pada DESC
  ");
  $logs = $stmt->fetchAll();
} catch (PDOException $e) {
  die("Kesalahan mengambil log perubahan: " . $e->getMessage());
}

function parse_trigger_log($data_string) {
  if (empty($data_string)) return [];
  
  $pairs = explode(' | ', $data_string);
  $parsed = [];
  
  foreach ($pairs as $pair) {
    $parts = explode('=', $pair);
    if (count($parts) === 2) {
      $key = trim($parts[0]);
      $val = trim($parts[1]);
      
      $friendly_key = $key;
      if ($key === 'nama') $friendly_key = 'Nama Resep';
      if ($key === 'kategori_id') $friendly_key = 'ID Kategori';
      if ($key === 'kesulitan') $friendly_key = 'Tingkat Kesulitan';
      
      $parsed[$friendly_key] = $val;
    }
  }
  return $parsed;
}
?>

<div class="retro-card">
  <div class="mb-4">
    <h2 class="card-title-retro text-danger italic-serif mb-1"><i class="bi bi-clock-history"></i> Log Perubahan Resep</h2>
    <p class="text-muted mb-0">Halaman ini memantau perubahan data resep yang dicatat secara otomatis oleh trigger database <strong><code>log_perubahan_resep</code></strong>.</p>
  </div>

  <div class="border-retro-bottom my-4"></div>

  <div class="table-responsive">
    <table class="table table-hover table-retro align-middle">
      <thead>
        <tr>
          <th>Tanggal & Waktu</th>
          <th>ID Resep</th>
          <th>Nama Resep Terkini</th>
          <th class="text-center">Aksi</th>
          <th>Perubahan Data (Lama vs Baru)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($logs) > 0): ?>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td class="font-monospace small">
                <?php echo date('d-m-Y H:i:s', strtotime($log['diubah_pada'])); ?>
              </td>
              <td class="font-monospace fw-bold text-center">
                #<?php echo $log['resep_id']; ?>
              </td>
              <td>
                <strong class="text-danger"><?php echo htmlspecialchars($log['nama_resep'] ?: '(Resep Telah Dihapus)'); ?></strong>
              </td>
              <td class="text-center">
                <span class="badge bg-warning text-dark px-3 rounded-pill text-uppercase" style="font-size: 0.75rem;">
                  <?php echo htmlspecialchars($log['aksi']); ?>
                </span>
              </td>
              <td>
                <?php 
                  $old_data = parse_trigger_log($log['data_lama']);
                  $new_data = parse_trigger_log($log['data_baru']);
                ?>
                <div class="row g-2 font-monospace small my-1">
                  <!-- Old values -->
                  <div class="col-sm-6">
                    <div class="p-2 rounded border border-danger bg-light-danger" style="background-color: #fdf5f6;">
                      <div class="fw-bold text-danger mb-1 border-bottom pb-1" style="font-size: 0.7rem;">SEBELUM:</div>
                      <?php foreach ($old_data as $k => $v): ?>
                        <div class="text-muted"><strong class="text-dark"><?php echo htmlspecialchars($k); ?>:</strong> <?php echo htmlspecialchars($v); ?></div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  
                  <div class="col-sm-6">
                    <div class="p-2 rounded border border-success bg-light-success" style="background-color: #f6fdf8;">
                      <div class="fw-bold text-success mb-1 border-bottom pb-1" style="font-size: 0.7rem;">SESUDAH:</div>
                      <?php foreach ($new_data as $k => $v): ?>
                        <div><strong class="text-dark"><?php echo htmlspecialchars($k); ?>:</strong> <?php echo htmlspecialchars($v); ?></div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center py-4 text-muted">
              Belum ada log aktivitas yang tercatat. Lakukan edit data resep pada katalog untuk memicu trigger database.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'footer.php'; ?>
