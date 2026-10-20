<h2>Tes koneksi gagal</h2>
<p>Pesan di bawah router menjelaskan penyebabnya. Yang paling sering:</p>
<table>
    <thead><tr><th>Pesan atau gejala</th><th>Periksa</th></tr></thead>
    <tbody>
        <tr><td>Timeout, connection refused, "unable to connect"</td><td>Apakah <code>/ip service api</code> aktif? Apakah alamat dan portnya benar? Apakah firewall atau pembatasan <em>address</em> pada layanan memblokir komputer Fluxmon? Bisakah Anda <code>ping</code> router dari komputer itu?</td></tr>
        <tr><td>"Invalid user name or password"</td><td>Ketik ulang akun dan password. Ubah router; kosongkan password hanya jika ingin mempertahankan yang lama.</td></tr>
        <tr><td>Berhasil, tetapi beberapa halaman menyebut perintah tidak diizinkan</td><td>Group akun kekurangan policy. Tambahkan <code>write</code> untuk perubahan hotspot, <code>test</code> untuk pengecekan ping.</td></tr>
        <tr><td>API-SSL tidak terhubung</td><td>Centang <em>{{ __('Use API-SSL (port 8729)') }}</em> <strong>dan</strong> atur port API ke 8729; router butuh sertifikat yang ditetapkan pada layanan <code>api-ssl</code>.</td></tr>
    </tbody>
</table>

<h2>Grafik pemantauan kosong</h2>
<p>Jalankan <a href="{{ route('docs', 'scheduler') }}">scheduler</a>. Titik muncul satu per menit; traffic butuh dua sampel. Periksa dengan <code>php artisan fluxmon:sync</code>, yang seharusnya mencetak <em>online</em> untuk setiap router.</p>

<h2>Voucher tidak dihitung, atau tidak kedaluwarsa</h2>
<ul>
    <li>Tekan <strong>{{ __('Sync') }}</strong> pada {{ __('Overview') }} router. Jika voucher kini terhitung, berarti scheduler tidak berjalan.</li>
    <li>Voucher dihitung hanya setelah dipakai minimal sekali. Kartu yang belum pernah dipakai login bukan penjualan.</li>
    <li>Hanya voucher yang dibuat dengan form <em>{{ __('Generate') }}</em> yang dilacak; pengguna hotspot buatan manual tidak.</li>
</ul>

<h2>Backup gagal</h2>
<ul>
    <li><strong>SSH login failed:</strong> aktifkan <code>/ip service ssh</code>, periksa port SSH di pengaturan router, dan pastikan akun punya policy <code>ssh</code>.</li>
    <li><strong>"host key changed":</strong> lihat <a href="{{ route('docs', 'backups') }}">Backup konfigurasi</a>. Hapus kunci hanya jika router diganti.</li>
    <li><strong>"Empty export":</strong> akun mungkin kurang hak <code>read</code> atau <code>policy</code>.</li>
</ul>

<h2>Pesan Telegram tidak datang</h2>
<ul>
    <li>Tekan <em>{{ __('Send test message') }}</em>; error dari Telegram ditampilkan.</li>
    <li>Sudahkah Anda mengirim pesan ke bot lebih dulu? Bot tidak bisa memulai percakapan.</li>
    <li>Apakah chat ID benar? Untuk grup biasanya diawali <code>-</code>.</li>
    <li>Untuk peringatan, apakah <em>{{ __('Send alerts to Telegram') }}</em> dicentang? Laporan harian punya kotak centang sendiri.</li>
    <li>Bisakah komputer Fluxmon menjangkau <code>api.telegram.org</code> dari jaringannya?</li>
</ul>

<h2>Sebuah menu atau halaman tidak ada</h2>
<p>Peran Anda tidak mencakupnya (lihat <a href="{{ route('docs', 'accounts') }}">Akun dan peran</a>), atau akun Anda dibatasi pada router lain. Tanyakan kepada admin.</p>

<h2>Saya lupa password admin</h2>
<p>Orang yang punya akses ke server dapat mengatur yang baru. Di folder Fluxmon jalankan:</p>
<pre><code>php artisan tinker --execute="App\Models\User::where('email', 'you@example.com')->first()->update(['password' => 'password-baru']);"</code></pre>
<p>Lalu masuk dan ubah dari halaman akun Anda.</p>

<h2>Setelah memperbarui Fluxmon ada yang rusak</h2>
<pre><code>git pull
composer install
php artisan migrate
npm install &amp;&amp; npm run build</code></pre>
<div class="callout-warn">
    Selalu gunakan <code>php artisan migrate</code> untuk memperbarui database. <code>migrate:fresh</code> menghapus semua data.
</div>

<h2>Masih buntu?</h2>
<p>Catat pesan persisnya serta versi RouterOS dan board Anda (tampil di {{ __('Overview') }} router), lalu hubungi <a href="mailto:{{ config('fluxmon.developer.email') }}">{{ config('fluxmon.developer.email') }}</a>.</p>
