document.addEventListener('DOMContentLoaded', function() {
  const forms = document.querySelectorAll('.needs-validation');
  forms.forEach(form => {
    form.addEventListener('submit', function(event) {
      if (!form.checkValidity() || !validateCustomRules(form)) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

  if (document.getElementById('ingredients-container')) {
    calculateCosts();
  }

  setupDeleteConfirmations();
});

function validateCustomRules(form) {
  if (form.id === 'recipe-form') {
    const selects = form.querySelectorAll('.ingredient-select');
    const selectedIds = [];
    let hasDuplicates = false;
    let hasEmptySelection = false;
    let hasInvalidQty = false;

    selects.forEach(select => {
      const val = select.value;
      if (!val) {
        hasEmptySelection = true;
      } else {
        if (selectedIds.includes(val)) {
          hasDuplicates = true;
        }
        selectedIds.push(val);
      }
    });

    const qtys = form.querySelectorAll('.ingredient-qty');
    qtys.forEach(qty => {
      if (parseFloat(qty.value) <= 0 || isNaN(parseFloat(qty.value))) {
        hasInvalidQty = true;
      }
    });

    if (selects.length === 0) {
      showToastAlert('Resep harus memiliki minimal 1 bahan!', 'danger');
      return false;
    }

    if (hasEmptySelection) {
      showToastAlert('Silakan pilih bahan di setiap baris!', 'danger');
      return false;
    }

    if (hasDuplicates) {
      showToastAlert('Terdapat bahan ganda! Satu bahan hanya boleh dipilih sekali dalam resep.', 'danger');
      return false;
    }

    if (hasInvalidQty) {
      showToastAlert('Jumlah bahan harus lebih dari 0!', 'danger');
      return false;
    }
  }

  if (form.id === 'ingredient-form') {
    const stok = parseFloat(form.querySelector('#stok').value);
    const harga = parseFloat(form.querySelector('#harga_per_unit').value);

    if (stok < 0 || isNaN(stok)) {
      showToastAlert('Stok bahan tidak boleh bernilai negatif!', 'danger');
      return false;
    }
    if (harga < 0 || isNaN(harga)) {
      showToastAlert('Harga per unit tidak boleh bernilai negatif!', 'danger');
      return false;
    }
  }

  return true;
}


function addIngredientRow(selectedId = '', quantity = '', catatan = '') {
  const container = document.getElementById('ingredients-container');
  if (!container) return;

  const rowId = 'row-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
  const rowHtml = `
    <tr id="${rowId}" class="ingredient-row">
      <td>
        <select name="bahan_id[]" class="form-select ingredient-select" onchange="updateRowDetails('${rowId}')" required>
          <option value="">-- Pilih Bahan --</option>
          ${window.ALL_INGREDIENTS.map(ing => `
            <option value="${ing.id}" ${ing.id == selectedId ? 'selected' : ''}>
              ${ing.nama} (Stok: ${parseFloat(ing.stok)} ${ing.satuan})
            </option>
          `).join('')}
        </select>
      </td>
      <td>
        <div class="input-group">
          <input type="number" name="jumlah[]" step="0.01" class="form-control ingredient-qty" placeholder="Jumlah" value="${quantity}" oninput="calculateCosts()" required min="0.01">
          <span class="input-group-text ingredient-unit">ml</span>
        </div>
      </td>
      <td>
        <input type="text" name="catatan_bahan[]" class="form-control" placeholder="Catatan (opsional)" value="${catatan}">
      </td>
      <td>
        <span class="ingredient-cost font-monospace fw-bold text-danger">Rp0</span>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="removeIngredientRow('${rowId}')">
          <i class="bi bi-trash"></i> Hapus
        </button>
      </td>
    </tr>
  `;

  container.insertAdjacentHTML('beforeend', rowHtml);
  updateRowDetails(rowId);
  calculateCosts();
}

function removeIngredientRow(rowId) {
  const row = document.getElementById(rowId);
  if (row) {
    row.remove();
    calculateCosts();
  }
}

function updateRowDetails(rowId) {
  const row = document.getElementById(rowId);
  if (!row) return;

  const select = row.querySelector('.ingredient-select');
  const unitSpan = row.querySelector('.ingredient-unit');
  
  const selectedId = select.value;
  const ingredient = window.ALL_INGREDIENTS.find(ing => ing.id == selectedId);

  if (ingredient) {
    unitSpan.textContent = ingredient.satuan;
  } else {
    unitSpan.textContent = 'unit';
  }
  calculateCosts();
}

function calculateCosts() {
  const container = document.getElementById('ingredients-container');
  if (!container) return;

  const rows = container.querySelectorAll('.ingredient-row');
  let totalCost = 0;

  rows.forEach(row => {
    const select = row.querySelector('.ingredient-select');
    const qtyInput = row.querySelector('.ingredient-qty');
    const costSpan = row.querySelector('.ingredient-cost');

    const selectedId = select.value;
    const qty = parseFloat(qtyInput.value) || 0;
    const ingredient = window.ALL_INGREDIENTS.find(ing => ing.id == selectedId);

    if (ingredient && qty > 0) {
      const rowCost = qty * parseFloat(ingredient.harga_per_unit);
      totalCost += rowCost;
      costSpan.textContent = formatRupiah(rowCost);
    } else {
      costSpan.textContent = 'Rp0';
    }
  });

  const totalCostElement = document.getElementById('total-recipe-cost');
  if (totalCostElement) {
    totalCostElement.textContent = formatRupiah(totalCost);
  }
}

function formatRupiah(number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(number);
}

function showToastAlert(message, type = 'danger') {
  // Clear any existing alert banner first
  const existingAlert = document.getElementById('js-toast-alert');
  if (existingAlert) {
    existingAlert.remove();
  }

  const alertDiv = document.createElement('div');
  alertDiv.id = 'js-toast-alert';
  alertDiv.className = `alert alert-retro-${type} position-fixed top-0 start-50 translate-middle-x m-3 z-3 shadow-lg`;
  alertDiv.style.minWidth = '300px';
  alertDiv.innerHTML = `
    <div class="d-flex align-items-center justify-content-between">
      <span><i class="bi bi-exclamation-triangle-fill me-2"></i> ${message}</span>
      <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
    </div>
  `;
  document.body.appendChild(alertDiv);

  setTimeout(() => {
    if (document.body.contains(alertDiv)) {
      alertDiv.remove();
    }
  }, 5000);
}

function setupDeleteConfirmations() {
  document.body.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-confirm');
    if (btn) {
      e.preventDefault();
      const href = btn.getAttribute('href');
      const action = btn.dataset.action || 'menghapus data ini';
      
      let confirmModal = document.getElementById('retro-delete-modal');
      if (!confirmModal) {
        const modalHtml = `
          <div class="modal fade" id="retro-delete-modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title italic-serif text-danger"><i class="bi bi-exclamation-octagon"></i> Konfirmasi Penghapusan</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                  <p class="fs-5 mb-1" id="retro-delete-text">Apakah Anda yakin ingin menghapus data ini?</p>
                  <small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-retro-secondary" data-bs-dismiss="modal">Batal</button>
                  <a href="#" id="retro-delete-confirm-btn" class="btn btn-retro bg-danger border-danger">Ya, Hapus!</a>
                </div>
              </div>
            </div>
          </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        confirmModal = document.getElementById('retro-delete-modal');
      }

      document.getElementById('retro-delete-text').innerHTML = `Apakah Anda yakin ingin <strong>${action}</strong>?`;
      document.getElementById('retro-delete-confirm-btn').setAttribute('href', href);
      
      const bsModal = new bootstrap.Modal(confirmModal);
      bsModal.show();
    }
  });
}
