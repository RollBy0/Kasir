/*!
 * kasir.js
 * JavaScript untuk Aplikasi Kasir (PHP + MySQL + AdminLTE 2 / Bootstrap 3 / jQuery 3)
 *
 * CARA PASANG (cukup 3 baris, tidak mengubah logika PHP):
 *   1. admin/footer.php   -> <script src="../assets/js/kasir.js"></script>   (paling bawah, setelah demo.js)
 *   2. petugas/footer.php -> <script src="../assets/js/kasir.js"></script>   (sama)
 *   3. index.php (login)  -> <script src="assets/js/kasir.js"></script>      (sebelum </body>)
 *
 * Setiap fitur mendeteksi sendiri apakah elemennya ada di halaman. Jadi file ini
 * aman dimuat di semua halaman: kalau elemennya tidak ada, fitur itu diam saja.
 *
 * DAFTAR FITUR
 *   1. Menu sidebar otomatis menyala sesuai halaman yang dibuka
 *   2. Konfirmasi sebelum menghapus (produk, pelanggan, kasir, barang di keranjang)
 *   3. Kotak pencarian untuk tabel data
 *   4. Form produk    : validasi + perbaikan harga "Rp. 15,000,-" sebelum dikirim
 *   5. Form pelanggan : validasi nama, alamat, nomor HP
 *   6. Form kasir     : validasi + cegah password ter-hash dua kali + role tidak salah pilih
 *   7. Transaksi      : info harga/stok, subtotal langsung, cek stok, kalkulator kembalian,
 *                       konfirmasi simpan, cegah klik ganda
 *   8. Laporan        : validasi rentang tanggal
 *   9. Login          : validasi + tombol "Tampilkan password"
 */
(function ($) {
  'use strict';

  if (!$) {
    console.error('[kasir.js] jQuery belum dimuat. Pastikan kasir.js dipasang SETELAH jquery.min.js.');
    return;
  }

  /* =====================================================================
   * 1. PENGATURAN (ubah di sini kalau aturan database/dosen berubah)
   * ===================================================================== */
  const CFG = {
    maksNama: 25,        // kolom nama di database = varchar(25)
    minPassword: 4,      // panjang password minimal
    telpMin: 8,          // panjang nomor HP minimal
    telpMax: 15,         // kolom NomorTelepon = varchar(15)
    wajibBayar: false    // true = transaksi baru bisa disimpan setelah "Uang Dibayar" diisi
  };

  /* =====================================================================
   * 2. FUNGSI BANTU
   * ===================================================================== */

  // 45000 -> "Rp. 45.000,-"
  function rupiah(n) {
    return 'Rp. ' + Math.round(Number(n) || 0).toLocaleString('id-ID') + ',-';
  }

  // "Rp. 15,000,-" / "15.000" / "15000" -> 15000. Kalau bukan angka bulat -> NaN
  function parseRupiah(teks) {
    const t = String(teks == null ? '' : teks)
      .trim()
      .replace(/^rp\.?\s*/i, '')   // buang awalan "Rp." / "Rp"
      .replace(/,-$/, '')          // buang akhiran ",-"
      .replace(/\s+/g, '');
    if (/^\d+$/.test(t)) return parseInt(t, 10);                                    // 15000
    if (/^\d{1,3}([.,]\d{3})+$/.test(t)) return parseInt(t.replace(/[.,]/g, ''), 10); // 15.000 atau 15,000
    return NaN;                                                                     // 15000.50, abc, kosong
  }

  // Tanggal lokal "YYYY-MM-DD" (sengaja tidak pakai toISOString karena memakai zona waktu UTC)
  function tglLokal(d) {
    const dua = (x) => String(x).padStart(2, '0');
    return d.getFullYear() + '-' + dua(d.getMonth() + 1) + '-' + dua(d.getDate());
  }

  // Tempat menampilkan pesan error di dalam sebuah form
  function wadahPesan($form) {
    const $w = $form.find('.modal-body, .box-body').first();
    return $w.length ? $w : $form;
  }

  function bersihkan($form) {
    $form.find('.js-pesan').remove();
    $form.find('.has-error').removeClass('has-error');
  }

  // Tampilkan pesan error (kotak merah Bootstrap) + tandai & fokus ke field yang salah
  function salah($form, $input, teks) {
    wadahPesan($form).prepend($('<div class="alert alert-danger js-pesan" role="alert"></div>').text(teks));
    if ($input && $input.length) {
      $input.closest('.form-group').addClass('has-error');
      $input.trigger('focus');
    }
  }

  // Cegah klik ganda: tombol submit dikunci setelah form lolos validasi
  function kunciTombol($form) {
    $form.find('[type="submit"]').each(function () {
      const $b = $(this);
      if ($b.prop('disabled')) return;
      $b.data('teks-asli', $b.html()).html('Memproses...').prop('disabled', true).addClass('js-terkunci');
    });
  }

  // Tombol "Back" di browser bisa memulihkan halaman dengan tombol masih terkunci -> buka lagi
  $(window).on('pageshow', function () {
    $('.js-terkunci').each(function () {
      $(this).html($(this).data('teks-asli')).prop('disabled', false).removeClass('js-terkunci');
    });
  });

  // Pasang validasi ke form. Fungsi "periksa" mengembalikan null (lolos) atau {el, teks} (gagal).
  function validasi(selectorForm, periksa) {
    $(document).on('submit', selectorForm, function (e) {
      const $f = $(this);
      bersihkan($f);
      const gagal = periksa($f);
      if (gagal) {
        e.preventDefault();
        salah($f, gagal.el, gagal.teks);
        return;
      }
      kunciTombol($f);
    });
  }

  // Pesan error hilang sendiri begitu user mulai memperbaiki isian
  $(document).on('input change', 'form', function () {
    bersihkan($(this));
  });

  // Jendela konfirmasi (Bootstrap modal). Kalau plugin modal tidak ada -> pakai confirm() biasa.
  let $dlg = null;
  function konfirmasi(opsi) {
    if (!$.fn.modal) {
      if (window.confirm(opsi.pesan)) opsi.onYa();
      return;
    }
    if (!$dlg) {
      $dlg = $(
        '<div class="modal fade" id="kasir-konfirmasi" tabindex="-1" role="dialog">' +
          '<div class="modal-dialog modal-sm" role="document"><div class="modal-content">' +
            '<div class="modal-header">' +
              '<button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>' +
              '<h4 class="modal-title"></h4>' +
            '</div>' +
            '<div class="modal-body"><p class="js-isi"></p></div>' +
            '<div class="modal-footer">' +
              '<button type="button" class="btn btn-default js-batal" data-dismiss="modal">Batal</button>' +
              '<button type="button" class="btn js-ya"></button>' +
            '</div>' +
          '</div></div>' +
        '</div>'
      ).appendTo('body');

      $dlg.on('click', '.js-ya', function () {
        const aksi = $dlg.data('aksi');
        $dlg.modal('hide');
        if (aksi) aksi();
      });
      $dlg.on('shown.bs.modal', function () {
        $dlg.find($dlg.data('fokus') === 'ya' ? '.js-ya' : '.js-batal').trigger('focus');
      });
    }
    $dlg.find('.modal-title').text(opsi.judul || 'Konfirmasi');
    $dlg.find('.js-isi').text(opsi.pesan);   // .text() supaya nama produk tidak dibaca sebagai HTML
    $dlg.find('.js-ya').attr('class', 'btn js-ya ' + (opsi.kelas || 'btn-primary')).text(opsi.tombol || 'Ya');
    $dlg.data('aksi', opsi.onYa).data('fokus', opsi.fokus || 'batal');
    $dlg.modal('show');
  }

  /* =====================================================================
   * 3. FITUR
   * ===================================================================== */

  /* ---------- 3.1 Menu sidebar aktif ---------- */
  function menuAktif() {
    const ALIAS = { 'transaksi_tambah.php': 'transaksi_data.php' };  // halaman yang tidak ada di menu
    let file = location.pathname.split('/').pop() || 'index.php';
    file = ALIAS[file] || file;

    $('.sidebar-menu a[href]').each(function () {
      if ($(this).attr('href') !== file) return;
      const $li = $(this).parent('li').addClass('active');
      const $induk = $li.parents('li.treeview').addClass('active menu-open');
      $induk.children('.treeview-menu').show();
    });
  }

  /* ---------- 3.2 Konfirmasi hapus ---------- */
  function konfirmasiHapus() {
    const JENIS = {
      'produk_hapus.php': 'produk',
      'pelanggan_hapus.php': 'pelanggan',
      'kasir_hapus.php': 'akun kasir',
      'transaksi_barang_hapus.php': 'barang'
    };

    $(document).on('click', 'a[href*="_hapus.php"]', function (e) {
      e.preventDefault();
      const href = this.getAttribute('href');
      const file = href.split('?')[0].split('/').pop();
      const jenis = JENIS[file] || 'data';
      // Nama diambil dari kolom ke-2 pada baris tabel tempat tombol hapus berada
      const nama = $.trim($(this).closest('tr').children('td').eq(1).clone().children().remove().end().text());
      const label = nama ? jenis + ' "' + nama + '"' : jenis + ' ini';

      const pesan = (file === 'transaksi_barang_hapus.php')
        ? 'Hapus ' + label + ' dari transaksi? Stok produk akan dikembalikan.'
        : 'Hapus ' + label + '? Data yang dihapus tidak bisa dikembalikan.';

      konfirmasi({
        judul: 'Konfirmasi Hapus',
        pesan: pesan,
        tombol: 'Ya, hapus',
        kelas: 'btn-danger',
        fokus: 'batal',                       // supaya tidak sengaja terhapus saat menekan Enter
        onYa: function () { KasirJS.pindah(href); }
      });
    });
  }

  /* ---------- 3.3 Pencarian di tabel data ---------- */
  function cariTabel() {
    // Halaman "tambah transaksi" berisi keranjang, bukan tabel data -> lewati
    if ($('form[action$="transaksi_proses.php"]').length) return;

    $('.content-wrapper table.table').each(function () {
      const $tabel = $(this);
      if (!$tabel.find('thead').length) return;           // tabel filter laporan tidak punya thead
      const $baris = $tabel.find('tbody tr');
      if (!$baris.length) return;

      // Simpan teks tiap baris, TANPA isi modal edit (kalau tidak, kata "Harga"/"Stok" cocok di semua baris)
      $baris.each(function () {
        const $salin = $(this).clone();
        $salin.find('.modal').remove();
        $(this).data('cari', $salin.text().replace(/\s+/g, ' ').toLowerCase());
      });

      const kolom = $tabel.find('thead th').length;
      const $kosong = $('<tr class="js-kosong" style="display:none"><td class="text-center text-muted" colspan="' + kolom +
        '">Tidak ada data yang cocok.</td></tr>');
      $tabel.find('tbody').append($kosong);

      const $kotak = $(
        '<div class="js-cari" style="max-width:280px;margin-bottom:10px">' +
          '<div class="input-group input-group-sm">' +
            '<span class="input-group-addon"><i class="fa fa-search"></i></span>' +
            '<input type="search" class="form-control" placeholder="Cari data..." autocomplete="off">' +
          '</div>' +
          '<small class="text-muted js-info"></small>' +
        '</div>'
      );
      $tabel.before($kotak);

      const $info = $kotak.find('.js-info');
      function saring() {
        const kata = $.trim($kotak.find('input').val()).toLowerCase();
        let tampil = 0;
        $baris.each(function () {
          const cocok = !kata || String($(this).data('cari')).indexOf(kata) !== -1;
          $(this).toggle(cocok);
          if (cocok) tampil++;
        });
        $kosong.toggle(tampil === 0);
        $info.text(kata ? 'Menampilkan ' + tampil + ' dari ' + $baris.length + ' data' : '');
      }
      $kotak.on('input', 'input', saring);
    });
  }

  /* ---------- 3.4 Form produk ---------- */
  function formProduk() {
    validasi('form[action$="produk_proses.php"], form[action$="produk_update.php"]', function ($f) {
      const $nama = $f.find('[name="nm_produk"]');
      const $harga = $f.find('[name="harga"]');
      const $stok = $f.find('[name="stok"]');

      const nama = $.trim($nama.val());
      if (!nama) return { el: $nama, teks: 'Nama produk wajib diisi.' };

      const harga = parseRupiah($harga.val());
      if (isNaN(harga)) return { el: $harga, teks: 'Harga harus berupa angka bulat tanpa pecahan, contoh: 15000.' };
      if (harga < 1) return { el: $harga, teks: 'Harga harus lebih dari 0.' };

      const stok = $.trim($stok.val());
      if (!/^\d+$/.test(stok)) return { el: $stok, teks: 'Stok harus bilangan bulat 0 atau lebih.' };

      // Kirim ke PHP dalam bentuk angka polos. Tanpa ini, harga "Rp. 15,000,-" dari form edit
      // membuat UPDATE di MySQL gagal diam-diam (perubahan tidak tersimpan).
      $nama.val(nama);
      $harga.val(harga);
      return null;
    });
  }

  /* ---------- 3.5 Form pelanggan ---------- */
  function formPelanggan() {
    validasi('form[action$="pelanggan_proses.php"], form[action$="pelanggan_update.php"]', function ($f) {
      const $nama = $f.find('[name="nm_pelanggan"]');
      const $alamat = $f.find('[name="alamat"]');
      const $hp = $f.find('[name="no_hp"]');

      if (!$.trim($nama.val())) return { el: $nama, teks: 'Nama pelanggan wajib diisi.' };
      if (!$.trim($alamat.val())) return { el: $alamat, teks: 'Alamat wajib diisi.' };

      const hp = $.trim($hp.val());
      const pola = new RegExp('^\\d{' + CFG.telpMin + ',' + CFG.telpMax + '}$');
      if (!pola.test(hp)) {
        return { el: $hp, teks: 'Nomor HP harus berupa angka ' + CFG.telpMin + '-' + CFG.telpMax + ' digit.' };
      }
      $nama.val($.trim($nama.val()));
      $alamat.val($.trim($alamat.val()));
      return null;
    });
  }

  /* ---------- 3.6 Form kasir ---------- */
  function formKasir() {
    validasi('form[action$="kasir_proses.php"], form[action$="kasir_update.php"]', function ($f) {
      const $nama = $f.find('[name="nm_user"]');
      const $user = $f.find('[name="username"]');
      const $sandi = $f.find('[name="password"]');

      if (!$.trim($nama.val())) return { el: $nama, teks: 'Nama petugas wajib diisi.' };

      const user = $.trim($user.val());
      if (!/^[A-Za-z0-9_.-]{3,25}$/.test(user)) {
        return { el: $user, teks: 'Username 3-25 karakter: huruf, angka, titik, strip, atau garis bawah (tanpa spasi).' };
      }

      const sandi = $sandi.val();
      const isEdit = /kasir_update\.php$/.test($f.attr('action') || '');
      // Form edit asli berisi hash md5 lama. Kalau dikirim begitu, PHP meng-hash-nya LAGI sehingga
      // akun tidak bisa login. Karena itu password baru wajib diisi saat edit.
      if (isEdit && (sandi === '' || sandi === $sandi.prop('defaultValue'))) {
        return { el: $sandi, teks: 'Isi password baru untuk menyimpan perubahan akun ini.' };
      }
      if (sandi.length < CFG.minPassword) {
        return { el: $sandi, teks: 'Password minimal ' + CFG.minPassword + ' karakter.' };
      }
      $nama.val($.trim($nama.val()));
      $user.val(user);
      return null;
    });
  }

  // Dijalankan setiap modal edit dibuka
  function siapkanModalEdit() {
    $(document).on('show.bs.modal', '.modal', function () {
      const $m = $(this);

      // Produk: tampilkan harga sebagai angka polos (bukan "Rp. 15,000,-") supaya enak diedit
      $m.find('form[action$="produk_update.php"] [name="harga"]').each(function () {
        const n = parseRupiah($(this).val());
        if (!isNaN(n)) $(this).val(n);
      });

      // Kasir: kosongkan password (isi aslinya hash md5) dan pilih role sesuai isi tabel.
      // Tanpa ini, mengedit petugas apa pun diam-diam mengubahnya menjadi Admin.
      $m.find('form[action$="kasir_update.php"]').each(function () {
        const $f = $(this);
        $f.find('[name="password"]').val('').attr('placeholder', 'Isi password baru (wajib)');
        const role = $.trim($m.closest('tr').children('td').eq(3).clone().children().remove().end().text()).toLowerCase();
        if (role) {
          $f.find('[name="role"] option').each(function () {
            if (this.value.toLowerCase() === role) $(this).prop('selected', true);
          });
        }
      });
    });
  }

  /* ---------- 3.7 Transaksi (halaman tambah transaksi) ---------- */
  function transaksi() {
    /* --- a. Modal "Tambah Barang" --- */
    const $modal = $('#tambah-transaksi');
    if ($modal.length) {
      const $form = $modal.find('form');
      const $produk = $form.find('[name="produk"]');
      const $jumlah = $form.find('[name="jumlah"]');

      // Baca harga & stok tiap pilihan produk satu kali saja.
      // Stok dibaca dari data-stok; kalau tidak ada, diambil dari teks "Nama (10)".
      // Harga dibaca dari data-harga (lihat panduan: tambahkan atribut ini di PHP).
      $produk.find('option').each(function () {
        const $o = $(this);
        if (!$o.val()) return;
        let harga = parseFloat($o.attr('data-harga'));
        let stok = parseInt($o.attr('data-stok'), 10);
        if (isNaN(stok)) {
          const m = $.trim($o.text()).match(/\((\d+)\)$/);
          stok = m ? parseInt(m[1], 10) : NaN;
        }
        $o.data('info', { harga: isNaN(harga) ? null : harga, stok: isNaN(stok) ? null : stok });
        if (!isNaN(stok) && stok <= 0) $o.prop('disabled', true).text($.trim($o.text()) + ' - habis');
      });

      const $info = $('<p class="help-block js-info-produk"></p>');
      const $sub = $('<div class="form-group js-subtotal" style="display:none">' +
        '<label>Subtotal</label><p class="form-control-static"><strong></strong></p></div>');
      $produk.closest('.form-group').append($info);
      $jumlah.closest('.form-group').after($sub);
      $jumlah.attr({ min: 1, step: 1 });

      const hitung = function () {
        const d = $produk.find('option:selected').data('info');
        const jml = parseInt($jumlah.val(), 10);
        if (!d) {
          $info.text('');
          $sub.hide();
          $jumlah.removeAttr('max');
          return;
        }
        const teks = [];
        if (d.harga !== null) teks.push('Harga: ' + rupiah(d.harga));
        if (d.stok !== null) teks.push('Stok tersedia: ' + d.stok);
        $info.text(teks.join('   |   '));
        if (d.stok !== null) $jumlah.attr('max', d.stok);
        if (d.harga !== null && jml > 0) $sub.show().find('strong').text(rupiah(d.harga * jml));
        else $sub.hide();
      };

      $produk.on('change', function () {
        if ($produk.val() && !$jumlah.val()) $jumlah.val(1);
        hitung();
        if ($produk.val()) $jumlah.trigger('focus').trigger('select');
      });
      $jumlah.on('input', hitung);

      $modal.on('shown.bs.modal', function () { $produk.trigger('focus'); });
      $modal.on('hidden.bs.modal', function () {
        $form[0].reset();
        bersihkan($form);
        hitung();
      });

      validasi('form[action$="transaksi_detail_proses.php"]', function ($f) {
        if (!$produk.val()) return { el: $produk, teks: 'Pilih produk terlebih dahulu.' };
        const d = $produk.find('option:selected').data('info') || {};
        const jml = $.trim($jumlah.val());
        if (!/^\d+$/.test(jml) || parseInt(jml, 10) < 1) {
          return { el: $jumlah, teks: 'Jumlah harus bilangan bulat minimal 1.' };
        }
        if (d.stok != null && parseInt(jml, 10) > d.stok) {
          return { el: $jumlah, teks: 'Stok tidak cukup. Stok tersedia: ' + d.stok + '.' };
        }
        return null;
      });
    }

    /* --- b. Panel kanan: pilih pelanggan, uang dibayar, simpan --- */
    const $simpan = $('form[action$="transaksi_proses.php"]');
    if ($simpan.length) {
      const total = function () { return parseFloat($simpan.find('[name="TotalHarga"]').val()) || 0; };

      // Kalkulator kembalian. Input ini sengaja TIDAK punya atribut name, jadi tidak ikut terkirim ke PHP
      // (tabel tb_penjualan memang belum punya kolom untuk uang bayar).
      const $bayarWadah = $(
        '<div class="form-group js-bayar-wadah">' +
          '<label>Uang Dibayar</label>' +
          '<div class="input-group"><span class="input-group-addon">Rp</span>' +
            '<input type="text" inputmode="numeric" class="form-control js-bayar" placeholder="0" autocomplete="off"></div>' +
          '<p class="help-block js-kembalian"></p>' +
        '</div>'
      );
      $simpan.find('[type="submit"]').first().before($bayarWadah);
      const $bayar = $bayarWadah.find('.js-bayar');
      const $hasil = $bayarWadah.find('.js-kembalian');

      const hitungKembali = function () {
        const dibayar = parseRupiah($bayar.val());
        const t = total();
        if (isNaN(dibayar) || dibayar === 0) {
          $hasil.attr('class', 'help-block js-kembalian').text('');
        } else if (dibayar >= t) {
          $hasil.attr('class', 'help-block text-success js-kembalian').text('Kembalian: ' + rupiah(dibayar - t));
        } else {
          $hasil.attr('class', 'help-block text-danger js-kembalian').text('Kurang: ' + rupiah(t - dibayar));
        }
      };
      $bayar.on('input', function () {
        const digit = this.value.replace(/\D/g, '').slice(0, 12);
        this.value = digit ? Number(digit).toLocaleString('id-ID') : '';   // 50000 -> "50.000" saat mengetik
        hitungKembali();
      });
      hitungKembali();

      $simpan.on('submit', function (e) {
        e.preventDefault();               // submit asli baru dijalankan setelah user menekan "Ya, simpan"
        const $f = $simpan;
        bersihkan($f);

        const t = total();
        const $pel = $f.find('[name="pelanggan"]');
        if (t <= 0) return salah($f, $(), 'Belum ada barang di transaksi ini. Klik "Tambah" untuk memasukkan barang.');
        if (!$pel.val()) return salah($f, $pel, 'Pilih pelanggan terlebih dahulu.');

        const dibayar = parseRupiah($bayar.val());
        if (CFG.wajibBayar && (isNaN(dibayar) || dibayar === 0)) {
          return salah($f, $bayar, 'Isi uang yang dibayarkan pelanggan.');
        }
        if (!isNaN(dibayar) && dibayar > 0 && dibayar < t) {
          return salah($f, $bayar, 'Uang dibayar kurang ' + rupiah(t - dibayar) + '.');
        }

        const no = $f.find('[name="no_trans"]').val();
        let pesan = 'Simpan transaksi ' + no + ' dengan total ' + rupiah(t) + '?';
        if (!isNaN(dibayar) && dibayar >= t && dibayar > 0) pesan += ' Kembalian: ' + rupiah(dibayar - t) + '.';

        konfirmasi({
          judul: 'Simpan Transaksi',
          pesan: pesan,
          tombol: 'Ya, simpan',
          kelas: 'btn-success',
          fokus: 'ya',
          onYa: function () {
            kunciTombol($f);
            $f[0].submit();               // submit bawaan browser tidak memicu event "submit" lagi
          }
        });
      });
    }
  }

  /* ---------- 3.8 Laporan ---------- */
  function laporan() {
    const $f = $('form[action$="transaksi_laporan.php"]');
    if (!$f.length) return;
    const $awal = $f.find('[name="tgl_awal"]');
    const $akhir = $f.find('[name="tgl_akhir"]');

    // Isi awal: tampilkan filter yang sedang aktif; kalau belum ada, pakai bulan ini
    const q = new URLSearchParams(location.search);
    const now = new Date();
    $awal.val(q.get('tgl_awal') || tglLokal(new Date(now.getFullYear(), now.getMonth(), 1)));
    $akhir.val(q.get('tgl_akhir') || tglLokal(now));

    validasi('form[action$="transaksi_laporan.php"]', function () {
      if (!$awal.val()) return { el: $awal, teks: 'Tanggal awal wajib diisi.' };
      if (!$akhir.val()) return { el: $akhir, teks: 'Tanggal akhir wajib diisi.' };
      if ($awal.val() > $akhir.val()) {            // format YYYY-MM-DD aman dibandingkan sebagai teks
        return { el: $akhir, teks: 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.' };
      }
      return null;
    });
  }

  /* ---------- 3.9 Login ---------- */
  function login() {
    const $f = $('form[action$="cek_login.php"]');
    if (!$f.length) return;
    const $user = $f.find('[name="username"]');
    const $sandi = $f.find('[name="password"]');

    $sandi.closest('.form-group').after(
      '<div class="checkbox"><label><input type="checkbox" class="js-lihat-sandi"> Tampilkan password</label></div>'
    );
    $f.on('change', '.js-lihat-sandi', function () {
      $sandi.attr('type', this.checked ? 'text' : 'password');
    });
    $user.trigger('focus');

    validasi('form[action$="cek_login.php"]', function () {
      if (!$.trim($user.val())) return { el: $user, teks: 'Username wajib diisi.' };
      if (!$sandi.val()) return { el: $sandi, teks: 'Password wajib diisi.' };
      return null;
    });
  }

  /* =====================================================================
   * 4. BATASAN PANJANG INPUT (mengikuti varchar(25) di database)
   * ===================================================================== */
  function batasPanjang() {
    $('[name="nm_produk"], [name="nm_pelanggan"], [name="nm_user"]').attr('maxlength', CFG.maksNama);
    $('form[action*="kasir_"] [name="username"]').attr('maxlength', CFG.maksNama);
  }

  /* =====================================================================
   * 5. JALANKAN SAAT HALAMAN SIAP
   * ===================================================================== */
  const KasirJS = {
    rupiah: rupiah,
    parseRupiah: parseRupiah,
    pindah: function (url) { window.location.href = url; }   // dipisah supaya mudah diuji
  };
  window.KasirJS = KasirJS;

  $(function () {
    [menuAktif, batasPanjang, konfirmasiHapus, cariTabel, formProduk, formPelanggan, formKasir,
      siapkanModalEdit, transaksi, laporan, login].forEach(function (fitur) {
      try {
        fitur();
      } catch (err) {
        // Satu fitur error tidak boleh mematikan fitur lain
        console.error('[kasir.js] fitur "' + fitur.name + '" gagal dijalankan:', err);
      }
    });
  });
})(window.jQuery);