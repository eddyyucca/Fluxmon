<p>Setiap orang yang memakai Fluxmon memiliki akun sendiri. <strong>Peran</strong> menentukan apa yang bisa dibuka akun itu, dan akun juga bisa dibatasi pada router tertentu. Admin mengelola akun di <em>{{ __('Settings') }} → {{ __('Accounts') }}</em>.</p>

<h2>Peran</h2>
<table>
    <thead><tr><th>Peran</th><th>Untuk</th><th>Bisa</th></tr></thead>
    <tbody>
        <tr><td><strong>Admin</strong></td><td>Pemilik, manajer IT</td><td>Semuanya: router, pengaturan, akun, backup, log audit.</td></tr>
        <tr><td><strong>Technician</strong></td><td>Staf jaringan</td><td>Pemantauan, pengguna dan profil hotspot, voucher, laporan, backup, peringatan dan pengecekan, log audit. Tidak bisa menambah router atau mengelola akun dan pengaturan.</td></tr>
        <tr><td><strong>Cashier</strong></td><td>Staf kasir</td><td>Menjual voucher, mengelola pengguna hotspot, melihat laporan dan pemantauan dasar. Tidak bisa mengubah profil, melihat backup, atau membuka pengaturan.</td></tr>
        <tr><td><strong>Viewer</strong></td><td>Pemilik yang hanya memantau</td><td>Hanya baca: dasbor, pemantauan, pengguna, dan laporan. Tidak bisa mengubah apa pun.</td></tr>
    </tbody>
</table>
<p>Menu yang tidak boleh Anda pakai disembunyikan. Jika Anda tetap membuka halaman itu lewat alamatnya, muncul pesan "tidak diizinkan".</p>

<h2>Menambah akun</h2>
<ol>
    <li>Buka <em>{{ __('Settings') }} → {{ __('Accounts') }}</em>, isi {{ __('Name') }}, {{ __('Email') }}, dan {{ __('Password') }}.</li>
    <li>Pilih perannya.</li>
    <li>Untuk peran selain admin, Anda bisa mencentang <strong>{{ __('Only some routers') }}</strong> dan memilih routernya. Tanpa itu, akun melihat semua router.</li>
    <li>Tekan <strong>{{ __('Add account') }}</strong> dan berikan passwordnya kepada orangnya.</li>
</ol>

<h2>Mengubah atau menghapus akun</h2>
<p>Di daftar, ubah peran atau pilihan router lalu tekan <strong>{{ __('Save') }}</strong>, atau tekan <strong>{{ __('Remove') }}</strong>. Anda tidak bisa mengubah peran sendiri atau menghapus akun sendiri, sehingga selalu ada admin yang tersisa.</p>
<p>Setiap orang dapat mengubah nama, email, dan password sendiri lewat nama akun di bagian bawah sidebar.</p>

<h2>Batasan router</h2>
<p>Akun yang dibatasi pada beberapa router hanya melihat router itu di sidebar, dasbor, laporan, dan peringatan. Router lain berperilaku seolah tidak ada. Ini cocok untuk kasir outlet yang hanya perlu melihat lokasinya sendiri.</p>

<h2>{{ __('Audit log') }}</h2>
<p><em>{{ __('Manage') }} → {{ __('Audit log') }}</em> (admin dan technician) mencatat siapa melakukan apa dan kapan: router ditambahkan, pengguna dan voucher dibuat, profil diubah, backup dibuat, pengaturan dan akun diubah, sesi diputus, beserta alamat IP-nya. Gunakan untuk menjawab "siapa yang mengubah ini?".</p>
