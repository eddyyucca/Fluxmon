<p><em>{{ __('Reports') }}</em> (sidebar) menampilkan penghasilan Anda dari voucher. Sebuah voucher dihitung terjual pada saat <strong>login pertamanya</strong>, dengan harga yang ditetapkan saat batch dibuat. Voucher yang tidak pernah dipakai bukan pendapatan.</p>

<h2>Pilih yang ingin dilihat</h2>
<ul>
    <li><strong>{{ __('Period') }}:</strong> {{ __('Today') }}, {{ __('Last 7 days') }}, {{ __('Last 30 days') }}, {{ __('This month') }}, {{ __('Last month') }}, atau {{ __('Custom range') }} (isi <em>{{ __('From') }}</em> dan <em>{{ __('To') }}</em>).</li>
    <li><strong>{{ __('Router') }}:</strong> satu router atau semuanya ({{ __('All routers') }}). Akun yang dibatasi pada router tertentu hanya melihat router itu.</li>
</ul>
<p>Tekan <strong>{{ __('Apply') }}</strong> untuk memperbarui.</p>

<h2>Isi halaman</h2>
<ul>
    <li><strong>Kartu:</strong> pendapatan, voucher terjual, harga rata-rata, pendapatan rata-rata per hari.</li>
    <li><strong>{{ __('Revenue per day') }}:</strong> grafik batang; arahkan kursor ke batang untuk melihat jumlah dan banyaknya voucher.</li>
    <li><strong>{{ __('By profile') }}</strong> dan <strong>{{ __('By router') }}:</strong> paket dan lokasi mana yang paling menghasilkan.</li>
</ul>

<h2>Ekspor</h2>
<p>Tombol <strong>CSV</strong> mengunduh setiap voucher terjual pada periode dan router yang dipilih: waktu terjual, router, username, profil, dan harga. Buka di Excel atau Google Sheets untuk pembukuan Anda sendiri.</p>

<h2>Kalau angkanya terlihat salah</h2>
<ul>
    <li><strong>Penjualan hari ini belum muncul.</strong> Angka diperbarui saat Fluxmon sinkron dengan router. Tekan <em>{{ __('Sync') }}</em> di halaman router, atau jalankan <a href="{{ route('docs', 'scheduler') }}">scheduler</a>.</li>
    <li><strong>Sebuah router menunjukkan 0.</strong> Hanya voucher yang dibuat di Fluxmon yang dihitung. Pengguna yang Anda buat manual bukan penjualan.</li>
    <li><strong>Mata uang berbeda.</strong> Total menjumlahkan angka apa adanya. Jika router memakai mata uang berbeda, laporkan tiap router terpisah.</li>
    <li><strong>Waktu.</strong> Hari mengikuti zona waktu yang diatur oleh <code>APP_TIMEZONE</code> di <code>.env</code> (bawaan <code>Asia/Jakarta</code>).</li>
</ul>
