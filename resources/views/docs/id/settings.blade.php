<p><em>{{ __('Manage') }} → {{ __('Settings') }}</em> tersedia untuk admin.</p>

<h2>{{ __('Language') }}</h2>
<p>Pilih English atau Bahasa Indonesia. Berlaku untuk semua orang yang memakai Fluxmon ini, termasuk dokumentasi ini.</p>

<h2>Telegram</h2>
<p>Telegram mengirim laporan penjualan harian dan peringatan Anda ke sebuah chat atau grup.</p>
<ol>
    <li>Di Telegram, bicara dengan <strong>@@BotFather</strong>, kirim <code>/newbot</code>, dan ikuti langkahnya. Anda akan mendapat <strong>token bot</strong>.</li>
    <li>Buka bot baru Anda dan kirim pesan apa saja (atau tambahkan ke grup dan tulis di sana). Bot tidak bisa mengirim pesan kepada Anda sebelum Anda menulis kepadanya.</li>
    <li>Cari <strong>chat ID</strong> Anda (misalnya lewat bot seperti @@userinfobot, atau dari halaman <code>getUpdates</code> bot Anda).</li>
    <li>Masukkan {{ __('Bot token') }} dan {{ __('Chat ID') }} di Fluxmon, lalu centang yang Anda inginkan:
        <ul>
            <li><strong>{{ __('Daily report at 23:55') }}:</strong> pendapatan dan voucher terjual hari ini, per router.</li>
            <li><strong>{{ __('Send alerts to Telegram') }}:</strong> router offline, pengecekan gagal, dan aturan ambang.</li>
        </ul>
    </li>
    <li>Tekan <strong>{{ __('Save') }}</strong>, lalu <strong>{{ __('Send test message') }}</strong>.</li>
</ol>
<p>Token disimpan terenkripsi. Biarkan kolom token kosong saat menyimpan untuk mempertahankan token yang tersimpan.</p>

<h2>{{ __('Other alert channels') }}</h2>
<ul>
    <li><strong>{{ __('Webhook URL') }}:</strong> menerima <code>POST</code> JSON untuk setiap peringatan (lihat <a href="{{ route('docs', 'alerts') }}">Peringatan</a>).</li>
    <li><strong>{{ __('Email recipients') }}:</strong> satu atau beberapa alamat dipisah koma. Pengiriman butuh pengaturan email (<code>MAIL_MAILER</code>, <code>MAIL_HOST</code>, <code>MAIL_USERNAME</code>…) di berkas <code>.env</code>. Sebelum diatur, email hanya masuk ke berkas log.</li>
</ul>
<p><strong>{{ __('Send test alert') }}</strong> mengirim pesan ke semua kanal yang diatur sekaligus.</p>

<h2>{{ __('Accounts') }}</h2>
<p>Lihat <a href="{{ route('docs', 'accounts') }}">Akun dan peran</a>.</p>

<h2>Pengaturan di .env</h2>
<p>Beberapa opsi diatur di berkas <code>.env</code> di samping aplikasi, lalu berlaku setelah aplikasi dimulai ulang:</p>
<table>
    <thead><tr><th>Variabel</th><th>Bawaan</th><th>Arti</th></tr></thead>
    <tbody>
        <tr><td><code>APP_TIMEZONE</code></td><td>Asia/Jakarta</td><td>Zona waktu untuk laporan dan jadwal.</td></tr>
        <tr><td><code>FLUXMON_METRICS_RAW_DAYS</code></td><td>7</td><td>Hari data pemantauan per menit.</td></tr>
        <tr><td><code>FLUXMON_METRICS_ROLLUP_DAYS</code></td><td>180</td><td>Hari rata-rata per jam.</td></tr>
        <tr><td><code>FLUXMON_BACKUPS_KEEP</code></td><td>30</td><td>Backup konfigurasi yang disimpan per router.</td></tr>
        <tr><td><code>MAIL_*</code></td><td>log</td><td>Email keluar untuk peringatan.</td></tr>
    </tbody>
</table>
