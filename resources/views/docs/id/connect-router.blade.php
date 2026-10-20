<p>Fluxmon berkomunikasi dengan router lewat <strong>API</strong> RouterOS (pemantauan, hotspot) dan lewat <strong>SSH</strong> (backup konfigurasi). Pada sebagian router keduanya nonaktif atau dibatasi secara bawaan, jadi siapkan router terlebih dahulu.</p>

<h2>1. Aktifkan layanan di router</h2>
<p>Di Winbox pilih <em>New Terminal</em> lalu jalankan:</p>
<pre><code>/ip service enable api
/ip service enable ssh</code></pre>
<p>API memakai port <code>8728</code> dan API-SSL yang aman memakai <code>8729</code>. SSH memakai port <code>22</code>.</p>

<h2>2. Buat akun khusus</h2>
<p>Jangan berikan login <code>admin</code> harian Anda ke Fluxmon. Buat akun terpisah agar bisa dihapus kapan saja:</p>
<pre><code>/user group add name=fluxmon policy=read,write,api,ssh,test
/user add name=fluxmon group=fluxmon password=&lt;password yang kuat&gt;</code></pre>
<ul>
    <li><code>read</code> dan <code>api</code> sudah cukup untuk pemantauan.</li>
    <li><code>write</code> diperlukan untuk mengelola user hotspot, profil, dan voucher.</li>
    <li><code>ssh</code> diperlukan untuk backup, <code>test</code> untuk pengecekan ping.</li>
</ul>
<div class="callout-warn">
    Versi RouterOS sedikit berbeda soal policy yang dibutuhkan sebuah perintah. Jika ada yang ditolak, pesan errornya tampil di Fluxmon; tambahkan policy yang kurang ke group.
</div>

<h2>3. Batasi siapa yang bisa mengakses layanan (disarankan)</h2>
<pre><code>/ip service set api address=192.168.88.0/24
/ip service set ssh address=192.168.88.0/24</code></pre>
<p>Gunakan alamat komputer atau jaringan tempat Fluxmon berjalan. Pastikan tidak ada aturan firewall yang memblokir port 8728 dan 22 dari komputer itu.</p>

<h2>4. Tambahkan router di Fluxmon</h2>
<p>Buka <em>{{ __('Routers') }} → {{ __('Add router') }}</em> (khusus admin) dan isi:</p>
<table>
    <thead><tr><th>Kolom</th><th>Arti</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Name') }}</td><td>Label bebas; tampil di menu dan laporan.</td></tr>
        <tr><td>{{ __('Host / IP') }}</td><td>Alamat router seperti terlihat dari Fluxmon.</td></tr>
        <tr><td>{{ __('API port') }} / {{ __('SSH port (for backups)') }}</td><td>Biasanya 8728 dan 22. Untuk API-SSL pakai 8729 dan centang <em>{{ __('Use API-SSL (port 8729)') }}</em>.</td></tr>
        <tr><td>{{ __('Username') }} / {{ __('Password') }}</td><td>Akun dari langkah 2. Password disimpan terenkripsi.</td></tr>
        <tr><td>{{ __('Hotspot name (shown on vouchers)') }}</td><td>Dicetak di voucher.</td></tr>
        <tr><td>{{ __('DNS name / login address') }}</td><td>Alamat tempat pelanggan membuka halaman login hotspot (misalnya <code>hotspot.local</code>). Dipakai pada QR code voucher; jika kosong dipakai alamat router.</td></tr>
        <tr><td>{{ __('Currency') }}</td><td>Tampil di samping harga, misalnya <code>Rp</code>.</td></tr>
        <tr><td>{{ __('Default traffic interface') }}</td><td>Interface yang ditampilkan pertama di grafik traffic langsung.</td></tr>
        <tr><td>{{ __('Timeout (seconds)') }}</td><td>Detik menunggu router sebelum menyerah.</td></tr>
    </tbody>
</table>

<h2>5. Uji koneksi</h2>
<p>Di daftar router tekan <strong>{{ __('Test') }}</strong>. Jika berhasil Anda melihat nama board dan versi, dan Fluxmon mencatat fitur yang didukung router (RouterOS 6 atau 7, WireGuard, paket wifi baru, LTE…). Informasi ini tampil sebagai lencana di halaman {{ __('Overview') }} router.</p>
<p>Jika tes gagal, pesannya menjelaskan sebabnya. Lihat <a href="{{ route('docs', 'troubleshooting') }}">Pemecahan masalah</a>.</p>
