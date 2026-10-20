<h2>{{ __('Dashboard') }}</h2>
<p>Dasbor merangkum semua router yang boleh dilihat akun Anda.</p>
<ul>
    <li><strong>Kartu pendapatan:</strong> pendapatan dan voucher terjual hari ini dan bulan ini.</li>
    <li><strong>Grafik pendapatan:</strong> 14 hari terakhir. <em>{{ __('Full report') }}</em> membuka <a href="{{ route('docs', 'reports') }}">Laporan</a>.</li>
    <li><strong>Kartu router:</strong> {{ __('Online') }} atau {{ __('Offline') }}, board dan versi RouterOS, uptime, pengguna online sekarang, pengguna hotspot, serta bar CPU dan memori. Diperbarui setiap 10 detik.</li>
    <li><strong>Banner merah</strong> muncul saat ada <a href="{{ route('docs', 'alerts') }}">peringatan</a> yang aktif. Klik untuk membuka halaman peringatan.</li>
    <li><strong>{{ __('Recent activity') }}:</strong> tindakan terakhir para admin (hanya terlihat oleh peran yang boleh melihat log audit).</li>
</ul>
<div class="callout">
    Pendapatan berasal dari catatan Fluxmon sendiri, sehingga tetap tampil saat router offline. Kartu router butuh koneksi langsung.
</div>

<h2>Ringkasan router</h2>
<p>Pilih router di pemilih bagian atas sidebar, atau klik kartunya. Halaman <em>{{ __('Overview') }}</em> menampilkan:</p>
<ul>
    <li><strong>Lencana</strong> identitas router, versi RouterOS, arsitektur, dan fitur yang terdeteksi.</li>
    <li><strong>{{ __('Online now') }} / {{ __('Hotspot users') }}</strong> dan <strong>pendapatan</strong> hari ini serta bulan ini.</li>
    <li><strong>{{ __('Live traffic') }}:</strong> download dan upload satu interface, diperbarui setiap 2 detik. Pilih interface di menu tarik-turun. Grafik menyimpan 40 pembacaan terakhir dan tidak disimpan permanen; untuk riwayat gunakan <a href="{{ route('docs', 'monitoring') }}">Pemantauan</a>.</li>
    <li><strong>{{ __('System') }}:</strong> CPU dan memori.</li>
    <li><strong>{{ __('Sync') }}</strong> (kanan atas): memeriksa router saat itu juga untuk voucher yang terpakai atau kedaluwarsa, tanpa menunggu menit berikutnya.</li>
</ul>
