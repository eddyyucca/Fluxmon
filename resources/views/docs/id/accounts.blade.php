<p>Fluxmon punya <strong>dua tingkat login</strong>. Semua orang masuk ke Fluxmon lebih dulu. Orang yang mengelola sebuah perangkat lalu masuk <strong>sekali lagi, ke perangkat itu</strong>, dengan akun yang hanya milik perangkat tersebut. Superadmin dan administrator melewati langkah kedua.</p>

<h2>Akun Fluxmon</h2>
<p>Dibuat oleh admin di <em>{{ __('Settings') }} → {{ __('Accounts') }}</em>. Peran menentukan sejauh mana akun menjangkau.</p>
<table>
    <thead><tr><th>Peran</th><th>Login kedua</th><th>Bisa</th></tr></thead>
    <tbody>
        <tr><td><strong>{{ __('Superadmin') }}</strong></td><td>Tidak pernah</td><td>Semuanya: semua perangkat, <strong>satu laporan gabungan semua perangkat</strong>, router, Pengaturan, akun Fluxmon, akun perangkat, log audit.</td></tr>
        <tr><td><strong>{{ __('Administrator') }}</strong></td><td>Tidak pernah</td><td>Semua perangkat, satu perangkat per kali: pemantauan, hotspot, voucher, backup, peringatan, laporan satu perangkat, akun perangkat, log audit. Tidak bisa membuka Pengaturan, menambah router, mengelola akun Fluxmon, atau melihat laporan gabungan.</td></tr>
        <tr><td><strong>{{ __('Staff') }}</strong></td><td>Untuk setiap perangkat</td><td>Tidak punya hak sendiri. Mereka masuk ke perangkat dengan akun perangkat itu, dan apa yang bisa dilakukan di sana bergantung pada peran akun tersebut.</td></tr>
    </tbody>
</table>
<p>Akun pertama, yang dibuat saat Fluxmon dijalankan pertama kali, adalah superadmin. Anda tidak bisa mengubah peran sendiri atau menghapus akun sendiri, sehingga selalu ada yang tersisa.</p>

<h2>{{ __('Device accounts') }}</h2>
<p>Setiap perangkat punya akun manajemennya sendiri, terpisah dari akun Fluxmon. Akun perangkat berlaku untuk <strong>perangkat itu saja</strong>: orang yang sama butuh akun lain untuk masuk ke perangkat kedua.</p>
<table>
    <thead><tr><th>Peran perangkat</th><th>Untuk</th><th>Bisa di perangkat itu</th></tr></thead>
    <tbody>
        <tr><td><strong>{{ __('Technician') }}</strong></td><td>Staf jaringan</td><td>Pemantauan, pengguna dan profil hotspot, voucher, laporan, backup, peringatan dan pengecekan. Tidak bisa mengelola akun perangkat.</td></tr>
        <tr><td><strong>{{ __('Cashier') }}</strong></td><td>Staf kasir</td><td>Menjual voucher, mengelola pengguna hotspot, melihat laporan dan pemantauan dasar. Tidak bisa mengubah profil atau melihat backup.</td></tr>
        <tr><td><strong>{{ __('Viewer') }}</strong></td><td>Yang hanya memantau</td><td>Hanya baca: pemantauan, pengguna, dan laporan perangkat itu.</td></tr>
    </tbody>
</table>

<h3>Membuat akun perangkat</h3>
<ol>
    <li>Buka perangkatnya dan pilih <em>{{ __('Maintenance') }} → {{ __('Device accounts') }}</em> (superadmin dan administrator).</li>
    <li>Isi {{ __('Name') }}, {{ __('Device username') }} (huruf, angka, <code>.</code> <code>_</code> <code>-</code>), {{ __('Device password') }} minimal 8 karakter, dan {{ __('Role') }}.</li>
    <li>Tekan <strong>{{ __('Add account') }}</strong> dan berikan kredensialnya kepada orangnya.</li>
</ol>
<p>Untuk mengubah peran, mengatur password baru, atau menonaktifkan akun, tekan <strong>{{ __('Edit') }}</strong> pada barisnya. Perubahan berlaku pada klik berikutnya: akun yang dinonaktifkan atau dihapus langsung dikeluarkan.</p>

<h2>Cara staf masuk</h2>
<ol>
    <li>Masuk ke Fluxmon dengan akun Fluxmon berperan <strong>{{ __('Staff') }}</strong>.</li>
    <li>Di dasbor, perangkat yang belum dimasuki menampilkan <strong>{{ __('Sign in to this device') }}</strong>. Membuka perangkat dari menu memberi hasil yang sama.</li>
    <li>Masukkan username dan password perangkat. Jika berhasil perangkat terbuka, dan bilah atas menampilkan akun perangkat yang dipakai beserta <strong>{{ __('Sign out of device') }}</strong>.</li>
</ol>
<ul>
    <li><strong>Tiap perangkat terpisah:</strong> masuk ke satu perangkat tidak membuka perangkat lain.</li>
    <li><strong>Batas tidak aktif:</strong> login perangkat berakhir setelah 8 jam tanpa aktivitas (<code>FLUXMON_DEVICE_SESSION_MINUTES</code> di <code>.env</code>). Halaman yang menyegarkan diri di latar belakang tidak dihitung sebagai aktivitas.</li>
    <li><strong>Password salah:</strong> setelah 5 kali gagal dalam semenit, perangkat itu terkunci sebentar untuk orang tersebut. Perangkat lain tidak terpengaruh.</li>
    <li>Keluar dari Fluxmon mengakhiri semua login perangkat.</li>
</ul>

<h3>Perangkat mana yang terlihat oleh staf?</h3>
<p>Di <em>{{ __('Settings') }} → {{ __('Accounts') }}</em> akun Staf bisa dibatasi pada beberapa perangkat (<em>{{ __('Only some routers') }}</em>). Perangkat lain tidak ada bagi orang itu, sekalipun ia tahu password perangkatnya. Tanpa batasan, akun Staf boleh mencoba semua perangkat.</p>

<h2>Laporan dan dasbor</h2>
<ul>
    <li><strong>Superadmin:</strong> dasbor menampilkan pendapatan seluruh perusahaan, dan Laporan bisa menggabungkan <em>{{ __('All routers') }}</em> menjadi satu laporan.</li>
    <li><strong>Administrator dan Staf:</strong> laporan selalu untuk satu perangkat (Staf hanya untuk perangkat yang sedang mereka masuki), dan total seluruh perusahaan tidak ditampilkan.</li>
</ul>

<h2>{{ __('Audit log') }}</h2>
<p><em>{{ __('Manage') }} → {{ __('Audit log') }}</em> (superadmin dan administrator) mencatat siapa melakukan apa, kapan, dan dari alamat mana. Tindakan staf juga menyebut <strong>akun perangkat</strong> yang dipakai, misalnya <em>user.created carol [device account: Rina]</em>, dan login perangkat serta percobaan yang gagal juga tercatat.</p>
