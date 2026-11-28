/**
 * Interaksi halaman kasir KasirJayaJayaAtma.
 * - Sidebar mobile, modal, dialog konfirmasi hapus, toast
 * - Keranjang transaksi baru (POS) di halaman transaksi.php
 */
(function () {
  'use strict';

  var $ = function (selector, root) { return (root || document).querySelector(selector); };
  var $$ = function (selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); };

  var rupiah = function (value) {
    return 'Rp' + Math.round(value).toLocaleString('id-ID');
  };

  /* ---------- Sidebar (mobile) ---------- */
  var sidebar = $('#sidebar');
  var backdrop = $('.backdrop');
  function setSidebar(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    if (backdrop) backdrop.classList.toggle('is-hidden', !open);
  }
  $$('[data-sidebar-open]').forEach(function (el) { el.addEventListener('click', function () { setSidebar(true); }); });
  $$('[data-sidebar-close]').forEach(function (el) { el.addEventListener('click', function () { setSidebar(false); }); });

  /* ---------- Modal ---------- */
  function openModal(layer) {
    layer.classList.remove('is-hidden');
    var focusable = $('select, input:not([type="hidden"]), textarea, button.btn', layer);
    if (focusable) focusable.focus();
  }
  function closeModal(layer) {
    // Modal yang dirender server (detail, form ubah) ditutup dengan kembali ke URL daftar.
    if (layer.getAttribute('data-close-href')) {
      window.location.href = layer.getAttribute('data-close-href');
      return;
    }
    layer.classList.add('is-hidden');
  }
  $$('[data-modal-open]').forEach(function (el) {
    el.addEventListener('click', function () {
      var layer = document.getElementById(el.getAttribute('data-modal-open'));
      if (layer) openModal(layer);
    });
  });
  $$('[data-modal-close]').forEach(function (el) {
    el.addEventListener('click', function () {
      var layer = el.closest('.modal-layer');
      if (layer) closeModal(layer);
    });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    var open = $$('.modal-layer').filter(function (layer) { return !layer.classList.contains('is-hidden'); });
    if (open.length) closeModal(open[open.length - 1]);
    else setSidebar(false);
  });

  /* ---------- Dialog konfirmasi hapus ---------- */
  var confirmModal = $('#confirm-modal');
  var pendingForm = null;
  $$('form[data-confirm-message]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!confirmModal || form.getAttribute('data-confirmed') === '1') return;
      event.preventDefault();
      pendingForm = form;
      $('#confirm-title').textContent = form.getAttribute('data-confirm-title') || 'Konfirmasi';
      $('#confirm-message').textContent = form.getAttribute('data-confirm-message');
      $('#confirm-ok').textContent = form.getAttribute('data-confirm-label') || 'Hapus';
      openModal(confirmModal);
    });
  });
  if (confirmModal) {
    $('#confirm-ok').addEventListener('click', function () {
      if (!pendingForm) return;
      pendingForm.setAttribute('data-confirmed', '1');
      pendingForm.submit();
    });
  }

  /* ---------- Toast, cetak, auto-submit ---------- */
  var toast = $('[data-toast]');
  if (toast) window.setTimeout(function () { toast.remove(); }, 3200);

  $$('[data-print]').forEach(function (el) { el.addEventListener('click', function () { window.print(); }); });

  $$('[data-autosubmit]').forEach(function (input) {
    input.addEventListener('change', function () { if (input.form) input.form.submit(); });
  });

  /* ---------- Ketersediaan username akun ---------- */
  $$('input[data-cek-unik="username"]').forEach(function (input) {
    var form = input.form;
    var status = $('.cek-status', input.parentElement);
    var submit = $('button[type="submit"]', form);
    var timer = null;
    var request = null;
    var sequence = 0;
    var original = (input.getAttribute('data-cek-original') || '').trim().toLowerCase();

    function show(message, kind, blocked) {
      status.textContent = message;
      status.className = 'cek-status' + (kind ? ' ' + kind : '');
      submit.disabled = blocked;
    }
    input.addEventListener('input', function () {
      var value = input.value.trim();
      sequence += 1;
      var current = sequence;
      window.clearTimeout(timer);
      if (request) request.abort();
      if (!value || value.toLowerCase() === original) {
        show('', '', false);
        return;
      }
      if (value.length > 25) {
        show('Username maksimal 25 karakter.', 'error', true);
        return;
      }
      show('Memeriksa...', 'checking', true);
      timer = window.setTimeout(function () {
        request = new AbortController();
        var params = new URLSearchParams({ field: 'username', value: value, except_id: input.getAttribute('data-cek-except') || '0' });
        fetch('cek_ketersediaan.php?' + params.toString(), { signal: request.signal, credentials: 'same-origin' })
          .then(function (response) {
            if (!response.ok) throw new Error('Gagal memeriksa');
            return response.json();
          })
          .then(function (result) {
            if (current !== sequence) return;
            show(result.message, result.available ? 'success' : 'error', !result.available);
          })
          .catch(function (error) {
            if (current !== sequence || error.name === 'AbortError') return;
            show('Tidak dapat memeriksa sekarang. Username akan dicek saat disimpan.', 'warning', false);
          });
      }, 400);
    });
    form.addEventListener('submit', function (event) {
      if (submit.disabled) event.preventDefault();
    });
  });

  /* ---------- Detail transaksi: ubah status (Admin) ---------- */
  var statusSelect = $('#status-select');
  if (statusSelect) {
    var statusSubmit = $('#status-submit');
    var voidWarning = $('#void-warning');
    var syncStatus = function () {
      var current = statusSelect.getAttribute('data-current');
      var value = statusSelect.value;
      statusSubmit.disabled = statusSelect.disabled || value === current;
      statusSubmit.textContent = value === 'Cancelled' ? 'Setujui Void / Retur' : 'Simpan Status';
      if (voidWarning) voidWarning.classList.toggle('is-hidden', value !== 'Cancelled');
    };
    statusSelect.addEventListener('change', syncStatus);
    syncStatus();
  }

  /* ---------- Keranjang transaksi baru (POS) ---------- */
  var posForm = $('#pos-form');
  if (posForm) {
    var productSelect = $('#pos-product');
    var qtyInput = $('#pos-qty');
    var tbody = $('#pos-lines');
    var totalEl = $('#pos-total');
    var errorEl = $('#pos-error');
    var submitBtn = $('#pos-submit');
    var lines = []; // { id, qty }

    var products = {};
    $$('option', productSelect).forEach(function (option) {
      products[option.value] = {
        nama: option.getAttribute('data-nama'),
        harga: Number(option.getAttribute('data-harga')),
        stok: Number(option.getAttribute('data-stok'))
      };
    });

    var inCart = function (id) {
      var line = lines.filter(function (item) { return item.id === id; })[0];
      return line ? line.qty : 0;
    };
    var showError = function (message) {
      errorEl.textContent = message;
      errorEl.classList.toggle('is-hidden', !message);
    };

    var iconSvg = function (path) {
      return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + path + '" /></svg>';
    };
    var makeButton = function (className, label, path, onClick) {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = className;
      button.setAttribute('aria-label', label);
      button.innerHTML = iconSvg(path);
      button.addEventListener('click', onClick);
      return button;
    };
    var cell = function (text, className) {
      var td = document.createElement('td');
      if (className) td.className = className;
      if (text !== undefined) td.textContent = text;
      return td;
    };

    var setLineQty = function (id, next) {
      var product = products[id];
      if (next > product.stok) {
        showError('Stok ' + product.nama + ' hanya tersisa ' + product.stok + '.');
        return;
      }
      showError('');
      lines = next <= 0
        ? lines.filter(function (line) { return line.id !== id; })
        : lines.map(function (line) { return line.id === id ? { id: id, qty: next } : line; });
      render();
    };

    var render = function () {
      tbody.innerHTML = '';
      var total = 0;
      lines.forEach(function (line) {
        var product = products[line.id];
        var subtotal = product.harga * line.qty;
        total += subtotal;

        var tr = document.createElement('tr');
        tr.appendChild(cell(product.nama));
        tr.appendChild(cell(rupiah(product.harga), 'align-right'));

        var qtyCell = cell(undefined, 'align-center');
        var stepper = document.createElement('div');
        stepper.className = 'qty-stepper';
        stepper.appendChild(makeButton('', 'Kurangi', 'M5 12h14', function () { setLineQty(line.id, line.qty - 1); }));
        var count = document.createElement('span');
        count.textContent = line.qty;
        stepper.appendChild(count);
        stepper.appendChild(makeButton('', 'Tambah', 'M12 5v14M5 12h14', function () { setLineQty(line.id, line.qty + 1); }));
        qtyCell.appendChild(stepper);
        tr.appendChild(qtyCell);

        var subtotalCell = cell(undefined, 'align-right');
        var strong = document.createElement('strong');
        strong.textContent = rupiah(subtotal);
        subtotalCell.appendChild(strong);
        tr.appendChild(subtotalCell);

        var removeCell = cell();
        removeCell.appendChild(makeButton('icon-action danger', 'Hapus baris', 'M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6', function () { setLineQty(line.id, 0); }));
        tr.appendChild(removeCell);
        tbody.appendChild(tr);
      });

      if (!lines.length) {
        var empty = document.createElement('tr');
        var td = cell('Belum ada produk di keranjang.', 'empty-cell');
        td.colSpan = 5;
        empty.appendChild(td);
        tbody.appendChild(empty);
      }

      // Perbarui sisa stok pada pilihan produk
      $$('option', productSelect).forEach(function (option) {
        var product = products[option.value];
        var left = product.stok - inCart(option.value);
        option.disabled = left <= 0;
        option.textContent = product.nama + ' — ' + rupiah(product.harga) + ' (stok ' + left + ')';
      });

      totalEl.textContent = rupiah(total);
      submitBtn.disabled = lines.length === 0;
    };

    $('#pos-add').addEventListener('click', function () {
      var id = productSelect.value;
      var product = products[id];
      var qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);
      if (!product) return;
      if (inCart(id) + qty > product.stok) {
        showError('Stok ' + product.nama + ' hanya tersisa ' + product.stok + '.');
        return;
      }
      showError('');
      if (inCart(id)) setLineQty(id, inCart(id) + qty);
      else { lines.push({ id: id, qty: qty }); render(); }
      qtyInput.value = 1;
    });

    posForm.addEventListener('submit', function (event) {
      if (!lines.length) {
        event.preventDefault();
        showError('Tambahkan minimal satu produk.');
        return;
      }
      // Kirim keranjang sebagai produk_id[] dan jumlah[]
      $$('input[data-cart]', posForm).forEach(function (input) { input.remove(); });
      lines.forEach(function (line) {
        [['produk_id[]', line.id], ['jumlah[]', line.qty]].forEach(function (pair) {
          var input = document.createElement('input');
          input.type = 'hidden';
          input.name = pair[0];
          input.value = pair[1];
          input.setAttribute('data-cart', '');
          posForm.appendChild(input);
        });
      });
      submitBtn.disabled = true;
    });

    render();
  }
  /* ---------- Profil: pratinjau & validasi foto ---------- */
  var photoInput = $('#photo-input');
  if (photoInput) {
    var photoDrop = $('#photo-drop');
    var photoPreview = $('#photo-preview');
    var photoName = $('#photo-name');
    var photoError = $('#photo-error');
    var photoSubmit = $('#photo-submit');
    var maxBytes = Number(photoInput.getAttribute('data-max'));
    var allowed = ['image/jpeg', 'image/png', 'image/webp'];
    var previewUrl = null;

    var photoMessage = function (message) {
      photoError.textContent = message;
      photoError.classList.toggle('is-hidden', !message);
    };

    var handlePhoto = function () {
      var file = photoInput.files && photoInput.files[0];
      if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
      photoSubmit.disabled = true;
      photoName.classList.remove('chosen');
      if (!file) { photoName.textContent = 'Belum ada file dipilih'; photoMessage(''); return; }

      photoName.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
      if (allowed.indexOf(file.type) === -1) { photoMessage('Format foto harus JPG, PNG, atau WebP.'); return; }
      if (file.size > maxBytes) { photoMessage('Ukuran foto maksimal 2 MB.'); return; }

      photoMessage('');
      photoName.classList.add('chosen');
      previewUrl = URL.createObjectURL(file);
      var img = document.createElement('img');
      img.src = previewUrl;
      img.alt = '';
      photoPreview.innerHTML = '';
      photoPreview.appendChild(img);
      photoSubmit.disabled = false;
    };
    photoInput.addEventListener('change', handlePhoto);

    ['dragenter', 'dragover'].forEach(function (type) {
      photoDrop.addEventListener(type, function (event) { event.preventDefault(); photoDrop.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (type) {
      photoDrop.addEventListener(type, function (event) { event.preventDefault(); photoDrop.classList.remove('is-dragover'); });
    });
    photoDrop.addEventListener('drop', function (event) {
      if (!event.dataTransfer || !event.dataTransfer.files.length) return;
      photoInput.files = event.dataTransfer.files;
      handlePhoto();
    });
    $('#photo-form').addEventListener('submit', function () { photoSubmit.disabled = true; photoSubmit.textContent = 'Menyimpan...'; });
  }
})();
