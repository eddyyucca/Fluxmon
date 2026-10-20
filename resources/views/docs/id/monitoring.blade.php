<h2>Grafik pemantauan</h2>
<p><em>Router → {{ __('Monitoring') }}</em> menampilkan riwayat. Setiap menit scheduler mencatat CPU, memori, suhu (jika board punya sensor), voltase, pengguna online, dan traffic setiap interface.</p>
<ul>
    <li>Pilih periode: <strong>1h, 6h, 24h, 7d, atau 30d</strong>.</li>
    <li><strong>CPU / {{ __('Memory') }}</strong> dalam persen, <strong>{{ __('Online users') }}</strong>, <strong>{{ __('Traffic') }}</strong> (pilih interface), dan <strong>{{ __('Temperature') }}</strong> bila tersedia.</li>
    <li>Tabel di bawah mencantumkan setiap interface beserta kecepatannya pada sampel terakhir.</li>
</ul>
<div class="callout">
    <strong>Belum ada data?</strong> Grafik hanya terisi selama scheduler berjalan. Jalankan seperti di <a href="{{ route('docs', 'scheduler') }}">Scheduler</a>; titik pertama muncul dalam dua menit (traffic butuh dua sampel untuk menghitung kecepatan).
</div>

<h3>Berapa lama data disimpan</h3>
<p>Sampel per menit disimpan 7 hari, lalu dilebur menjadi rata-rata per jam yang disimpan 180 hari. Grafik satu hari atau kurang memakai data per menit; periode lebih panjang memakai rata-rata per jam. Kedua batas bisa diubah dengan <code>FLUXMON_METRICS_RAW_DAYS</code> dan <code>FLUXMON_METRICS_ROLLUP_DAYS</code> di <code>.env</code>.</p>

<h2>{{ __('Checks') }}</h2>
<p><em>Router → {{ __('Checks') }}</em> menguji setiap menit apakah sesuatu bisa dijangkau, dan memberi peringatan saat berhenti menjawab.</p>
<table>
    <thead><tr><th>Tipe</th><th>Cara kerja</th><th>Cocok untuk</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Ping from router') }}</td><td>Router sendiri melakukan ping ke alamat itu (2 paket).</td><td>"Apakah router ini bisa menjangkau gateway ISP atau server DNS-nya?"</td></tr>
        <tr><td>{{ __('TCP port from Fluxmon') }}</td><td>Server Fluxmon mencoba membuka port (batas 3 detik).</td><td>"Apakah server atau layanan router bisa dijangkau dari sini?"</td></tr>
    </tbody>
</table>
<ol>
    <li>Isi nama, pilih tipe, masukkan alamat (dan port untuk TCP), lalu tekan <strong>{{ __('Add check') }}</strong>.</li>
    <li>Hasil pertama muncul setelah menit berikutnya. Tabel menampilkan {{ __('Up') }}/{{ __('Down') }}, latensi, dan kapan terakhir dicek.</li>
    <li><strong>{{ __('Pause') }}</strong> menghentikan pengecekan tanpa menghapusnya. Ikon tempat sampah menghapusnya.</li>
</ol>
<p>Saat pengecekan mati, satu peringatan dikirim, dan pesan lain dikirim saat pulih. Lihat <a href="{{ route('docs', 'alerts') }}">Peringatan</a>.</p>

<h2>{{ __('Logs') }}</h2>
<p><em>Router → {{ __('Logs') }}</em> membaca log milik router. Entri terbaru di atas dan halaman menampilkan hingga 300 dari 1000 baris terakhir.</p>
<ul>
    <li><strong>{{ __('Topic') }}</strong> memfilter berdasarkan topik log (system, error, wireless, …); hanya topik yang ada di log yang dicantumkan.</li>
    <li><strong>{{ __('Search') }}</strong> memfilter berdasarkan teks dalam pesan.</li>
    <li><strong>{{ __('Auto refresh') }}</strong> memuat ulang setiap 5 detik. Error dan peringatan ditandai.</li>
</ul>
<p>Log diambil langsung dari router, jadi hanya sejauh yang disimpan router (biasanya beberapa ratus baris di memori).</p>
