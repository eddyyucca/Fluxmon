<p>Sebagian pekerjaan tidak bisa menunggu seseorang membuka halaman. Fluxmon mengerjakannya di latar belakang menurut jadwal yang disebut <strong>scheduler</strong>. Anda menjalankannya sekali dan membiarkannya berjalan.</p>

<div class="callout-warn">
    <strong>Tanpa scheduler</strong> panel tetap bisa dibuka dan halaman langsung tetap bekerja, tetapi: grafik pemantauan tetap kosong, peringatan tidak pernah muncul, pengecekan tidak berjalan, voucher tidak dihitung terjual atau kedaluwarsa sampai Anda menekan <em>{{ __('Sync') }}</em>, dan tidak ada backup malam atau laporan harian.
</div>

<h2>Apa yang berjalan dan kapan</h2>
<table>
    <thead><tr><th>Tugas</th><th>Kapan</th><th>Fungsinya</th></tr></thead>
    <tbody>
        <tr><td><code>fluxmon:sync</code></td><td>tiap menit</td><td>Untuk setiap router: menemukan voucher yang terpakai dan kedaluwarsa, mencatat CPU, memori, traffic, dan pembacaan lain, menjalankan pengecekan, mengevaluasi aturan peringatan, dan mengetahui saat router offline atau kembali.</td></tr>
        <tr><td><code>fluxmon:rollup</code></td><td>tiap jam</td><td>Melebur data per menit menjadi rata-rata per jam dan menghapus data yang melewati batas penyimpanan.</td></tr>
        <tr><td><code>fluxmon:backup</code></td><td>harian pukul 03:00</td><td>Menyimpan konfigurasi tiap router bila berubah.</td></tr>
        <tr><td><code>fluxmon:daily-report</code></td><td>harian pukul 23:55</td><td>Mengirim penjualan hari itu ke Telegram (jika diaktifkan).</td></tr>
    </tbody>
</table>

<h2>Menjalankannya</h2>
<h3>Saat mencoba, di komputer mana pun</h3>
<p>Buka terminal kedua di folder Fluxmon dan biarkan berjalan:</p>
<pre><code>php artisan schedule:work</code></pre>

<h3>Di server Linux (cron)</h3>
<pre><code>* * * * * cd /path/to/fluxmon &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>

<h3>Di Windows (Task Scheduler)</h3>
<ol>
    <li>Buka <em>Task Scheduler → Create Task</em>.</li>
    <li><em>Triggers:</em> trigger baru, harian, ulangi setiap <strong>1 menit</strong> tanpa batas waktu.</li>
    <li><em>Actions:</em> start a program: <code>php.exe</code> dengan argumen <code>artisan schedule:run</code>, dan <em>Start in</em> diisi folder Fluxmon.</li>
    <li>Centang <em>Run whether user is logged on or not</em>.</li>
</ol>

<h2>Menjalankan tugas manual</h2>
<pre><code>php artisan fluxmon:sync
php artisan fluxmon:backup
php artisan fluxmon:daily-report --force</code></pre>
<p>Masing-masing mencetak apa yang dikerjakannya untuk setiap router, yang juga cara cepat untuk melihat mengapa sesuatu gagal.</p>
