{{-- UI names are pulled from the interface translations so the guide always matches the screen. --}}
<p>Fluxmon adalah panel web untuk router MikroTik. Ia memantau router Anda, memperingatkan saat ada masalah, menyimpan salinan konfigurasinya, dan menjual voucher hotspot. Satu Fluxmon bisa mengelola router sebanyak yang Anda perlukan.</p>

<h2>Yang Anda butuhkan</h2>
<ul>
    <li>Router MikroTik dengan layanan <strong>API</strong> aktif (dan <strong>SSH</strong> jika ingin backup konfigurasi).</li>
    <li>Router harus bisa dijangkau dari komputer yang menjalankan Fluxmon.</li>
    <li>Scheduler berjalan di latar belakang (lihat <a href="{{ route('docs', 'scheduler') }}">Scheduler</a>). Grafik, peringatan, dan penjualan voucher bergantung padanya.</li>
</ul>

<h2>Langkah pertama</h2>
<ol>
    <li><strong>Buat akun superadmin.</strong> Kunjungan pertama menampilkan form pendaftaran. Setelah itu pendaftaran ditutup; superadmin menambah akun lain di <em>{{ __('Settings') }}</em> (lihat <a href="{{ route('docs', 'accounts') }}">Akun dan peran</a>).</li>
    <li><strong>Siapkan router</strong> lalu <strong>tambahkan</strong> di <em>{{ __('Routers') }} → {{ __('Add router') }}</em>. Lihat <a href="{{ route('docs', 'connect-router') }}">Menghubungkan router</a>.</li>
    <li><strong>Tekan {{ __('Test') }}.</strong> Lencana hijau berisi nama board dan versi RouterOS berarti Fluxmon bisa berkomunikasi dengan router.</li>
    <li><strong>Jalankan scheduler</strong> agar router disampel setiap menit.</li>
    <li>Opsional: buat <a href="{{ route('docs', 'alerts') }}">aturan peringatan</a> dan hubungkan Telegram di <a href="{{ route('docs', 'settings') }}">{{ __('Settings') }}</a>.</li>
</ol>

<h2>Susunan menu</h2>
<table>
    <thead><tr><th>Menu</th><th>Fungsinya</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Dashboard') }}, {{ __('Reports') }}, {{ __('Alerts') }}</td><td>Gambaran semua router Anda.</td></tr>
        <tr><td>Nama router ({{ __('Overview') }}, {{ __('Monitoring') }}, {{ __('Checks') }}, {{ __('Logs') }})</td><td>Memantau satu router. Pilih router lewat pemilih di bagian atas sidebar.</td></tr>
        <tr><td>{{ __('Hotspot') }} ({{ __('Users') }}, {{ __('Profiles') }}, {{ __('Vouchers') }}, {{ __('Active sessions') }})</td><td>Menjual dan mengelola akses hotspot di router itu.</td></tr>
        <tr><td>{{ __('Maintenance') }} ({{ __('Backups') }})</td><td>Salinan konfigurasi router yang tersimpan.</td></tr>
        <tr><td>{{ __('Manage') }} ({{ __('Routers') }}, {{ __('Settings') }}, {{ __('Audit log') }})</td><td>Administrasi. Anda hanya melihat yang diizinkan oleh peran Anda.</td></tr>
    </tbody>
</table>

<div class="callout">
    <strong>Tips:</strong> tombol bulan/matahari di bilah atas mengganti mode gelap, dan tombol <strong>EN | ID</strong> di sampingnya mengganti bahasa antara Inggris dan Indonesia. Pilihan Anda diingat untuk akun Anda.
</div>
