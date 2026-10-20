<p>Backup adalah salinan konfigurasi router (hasil <code>/export</code>) yang disimpan di Fluxmon. Jika router direset atau seseorang mengubah sesuatu secara keliru, Anda bisa melihat persis seperti apa sebelumnya.</p>

<h2>Sebelum mulai</h2>
<ul>
    <li>Layanan <strong>SSH</strong> harus aktif di router, dan Fluxmon butuh port SSH (bawaan 22) di pengaturan router.</li>
    <li>Akun router harus boleh login lewat SSH (policy <code>ssh</code>, lihat <a href="{{ route('docs', 'connect-router') }}">Menghubungkan router</a>).</li>
</ul>

<h2>Membuat dan membaca backup</h2>
<p>Buka <em>{{ __('Maintenance') }} → {{ __('Backups') }}</em> pada sebuah router.</p>
<ul>
    <li><strong>{{ __('Back up now') }}</strong> menyimpan konfigurasi saat ini.</li>
    <li>Fluxmon juga membackup <strong>setiap router tiap malam pukul 03:00</strong> (butuh <a href="{{ route('docs', 'scheduler') }}">scheduler</a>).</li>
    <li>Versi baru disimpan <strong>hanya bila ada perubahan</strong>. Jika tidak ada perubahan Anda diberi tahu, dan tidak dibuat duplikat.</li>
    <li>30 backup terbaru per router disimpan; yang lebih lama dihapus otomatis (<code>FLUXMON_BACKUPS_KEEP</code>).</li>
</ul>
<p>Setiap baris menunjukkan kapan disimpan, apakah <em>manual</em> atau <em>scheduled</em>, siapa yang memulai, dan ukurannya.</p>

<h2>Melihat apa yang berubah</h2>
<p>Klik sebuah backup (atau <strong>{{ __('Changes') }}</strong>). Backup terbuka sebagai perbandingan dengan versi sebelumnya: baris yang ditambahkan berwarna hijau dengan <code>+</code>, baris yang dihapus berwarna merah dengan <code>−</code>, dengan sedikit konteks yang tidak berubah di sekitarnya. Gunakan <strong>{{ __('Compare with') }}</strong> untuk memilih versi lain. Backup tertua tidak punya pembanding dan menampilkan konfigurasi lengkap.</p>
<p><strong>.rsc</strong> mengunduh backup sebagai berkas skrip RouterOS.</p>

<h2>Password dan rahasia</h2>
<p>RouterOS 7 secara bawaan tidak menyertakan nilai sensitif seperti password dalam ekspor, sehingga backup Fluxmon tidak memuatnya. Simpan sendiri catatan password dan kunci router; backup saja tidak memulihkannya.</p>

<h2>Keamanan koneksi SSH</h2>
<p>Saat pertama kali terhubung lewat SSH, Fluxmon mengingat <strong>host key</strong> router. Jika kunci itu kemudian berubah, backup berhenti dengan peringatan, karena itu bisa berarti ada yang menyadap koneksi. Jika Anda memang menginstal ulang atau mengganti router, admin dapat menekan <strong>{{ __('Clear') }}</strong> di samping <em>{{ __('SSH host key pinned:') }}</em> di bagian bawah halaman {{ __('Backups') }}; backup berikutnya menyematkan kunci yang baru.</p>
<div class="callout-warn">
    Jangan hapus kunci kecuali Anda tahu mengapa kunci itu berubah.
</div>
