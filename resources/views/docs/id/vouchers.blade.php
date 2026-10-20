<p>Voucher adalah login prabayar. Anda membuat satu batch, mencetak kartunya, lalu menjualnya. Fluxmon mengetahui saat setiap voucher pertama kali dipakai dan menghitungnya sebagai penjualan.</p>

<h2>Membuat batch</h2>
<p>Buka <em>{{ __('Hotspot') }} → {{ __('Vouchers') }}</em> dan isi form di sebelah kiri. Buat dulu <a href="{{ route('docs', 'hotspot') }}">profil</a> yang menentukan harga dan masa berlaku.</p>
<table>
    <thead><tr><th>Kolom</th><th>Arti</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Profile') }}</td><td>Menentukan kecepatan, harga, dan masa berlaku. Harga profil tampil di bawah kolom ini.</td></tr>
        <tr><td>{{ __('Quantity') }}</td><td>1 sampai 500 per batch.</td></tr>
        <tr><td>{{ __('Code length') }}</td><td>3 sampai 16 karakter. Enam adalah keseimbangan yang baik.</td></tr>
        <tr><td>{{ __('Prefix') }}</td><td>Opsional, maksimal 8 huruf, angka, <code>-</code>, atau <code>_</code>, diletakkan di depan setiap kode.</td></tr>
        <tr><td>{{ __('Characters') }}</td><td>{{ __('Digits') }}, {{ __('Lowercase') }}, {{ __('Uppercase') }}, atau {{ __('Lowercase + digits') }}. Karakter yang mirip (0/o, 1/l/i) dihilangkan agar kartu mudah dibaca.</td></tr>
        <tr><td>{{ __('Login type') }}</td><td><strong>{{ __('Code only') }}</strong>: kode menjadi username sekaligus password. <strong>{{ __('User + password') }}</strong>: dua kode berbeda.</td></tr>
        <tr><td>{{ __('Online time limit (optional)') }}</td><td>Total waktu voucher boleh online, dijalankan oleh router.</td></tr>
        <tr><td>{{ __('Data limit, MB') }}</td><td>Opsional.</td></tr>
        <tr><td>{{ __('Price override') }}</td><td>Opsional. Memakai harga ini, bukan harga profil, untuk batch ini.</td></tr>
    </tbody>
</table>
<p>Tekan <strong>{{ __('Generate') }}</strong>. Pengguna dibuat di router dan halaman batch terbuka. Jika ada yang gagal di tengah jalan, voucher yang sudah dibuat tetap disimpan dan error ditampilkan.</p>

<h2>Cetak</h2>
<p>Di halaman batch, tekan <strong>{{ __('Print') }}</strong>. Sebuah lembar terbuka di tab baru dengan satu kartu per voucher: nama hotspot, harga, kode (atau user dan password), profil, dan masa berlaku.</p>
<ul>
    <li><strong>{{ __('Size') }}:</strong> {{ __('Normal (3 per row)') }} atau {{ __('Small (5 per row)') }}, diatur untuk kertas A4.</li>
    <li><strong>{{ __('QR code') }}:</strong> memindainya membuka halaman login hotspot dengan kredensial sudah terisi. Alamatnya berasal dari <em>{{ __('DNS name / login address') }}</em> router.</li>
    <li><strong>{{ __('Unused only') }}:</strong> aktif secara bawaan, sehingga Anda tidak mencetak ulang kartu yang sudah terjual.</li>
    <li>Tekan tombol <strong>{{ __('Print') }}</strong> di lembar. Kartu memiliki garis putus-putus untuk memotong.</li>
</ul>
<p><strong>CSV</strong> di halaman batch mengunduh kode untuk dipakai di alat lain.</p>

<h2>Kapan voucher dianggap "terjual"?</h2>
<ol>
    <li>Setiap menit Fluxmon memeriksa router. Saat sebuah voucher pertama kali menunjukkan pemakaian, statusnya menjadi <strong>{{ __('Used') }}</strong> dan dihitung sebagai <strong>pendapatan</strong> sebesar harga yang ditetapkan saat batch dibuat.</li>
    <li>Jika profil punya masa berlaku, hitung mundur dimulai saat itu.</li>
    <li>Saat masa berlaku habis, Fluxmon memutus pengguna dan menghapus atau menonaktifkannya (sesuai profil), lalu menandai voucher <strong>{{ __('Expired') }}</strong>.</li>
</ol>
<div class="callout-warn">
    Deteksi dan kedaluwarsa berjalan di latar belakang. Tanpa scheduler (<a href="{{ route('docs', 'scheduler') }}">Scheduler</a>) tidak ada yang dihitung sampai Anda menekan <strong>{{ __('Sync') }}</strong>, dan voucher yang kedaluwarsa tetap aktif.
</div>
<p><strong>{{ __('Online time limit (optional)') }}</strong> dan <strong>masa berlaku</strong> berbeda. Batas waktu hanya menghitung menit saat voucher benar-benar online dan dijalankan oleh router. Masa berlaku adalah waktu kalender sejak login pertama dan ditangani Fluxmon.</p>

<h2>Menghapus batch</h2>
<p>Ikon tempat sampah menghapus voucher batch yang <strong>belum terpakai</strong> dari router dan dari Fluxmon. Voucher yang sudah terpakai tetap ada di catatan agar laporan Anda tetap benar.</p>
<p>Jika Anda menghapus voucher langsung di router, Fluxmon mengetahuinya pada sinkronisasi berikutnya: yang belum terpakai dilupakan begitu saja, yang sudah terpakai tetap dihitung di laporan dan ditandai kedaluwarsa.</p>
