<h2>{{ __('Users') }}</h2>
<p><em>{{ __('Hotspot') }} → {{ __('Users') }}</em> menampilkan pengguna hotspot di router, 50 per halaman.</p>
<ul>
    <li><strong>{{ __('Filter') }}</strong> berdasarkan nama atau komentar, profil, atau status: <em>{{ __('Online') }}</em>, <em>{{ __('Disabled') }}</em>, atau <em>{{ __('Never used') }}</em>.</li>
    <li>Setiap baris menampilkan profil, status, uptime, dan data terpakai (beserta batasnya jika ada). Voucher yang Anda buat juga muncul di sini.</li>
</ul>

<h3>Tambah pengguna</h3>
<p>Tekan <strong>{{ __('Add user') }}</strong>, lalu isi {{ __('Username') }} (tanpa spasi), {{ __('Password') }}, dan {{ __('Profile') }}. Secara opsional atur <strong>{{ __('Time limit (optional)') }}</strong> dan <strong>{{ __('Data limit, MB (optional)') }}</strong>; keduanya dijalankan oleh router. {{ __('Comment') }} berupa teks bebas.</p>

<h3>Ubah banyak pengguna sekaligus</h3>
<p>Centang pengguna (kotak di header memilih seluruh halaman), pilih aksi, lalu tekan <strong>{{ __('Apply') }}</strong>:</p>
<table>
    <thead><tr><th>Aksi</th><th>Efek</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Enable') }} / {{ __('Disable') }}</td><td>Mengizinkan atau memblokir login tanpa menghapus pengguna.</td></tr>
        <tr><td>{{ __('Reset counters') }}</td><td>Mengembalikan uptime dan data terpakai ke nol.</td></tr>
        <tr><td>{{ __('Delete') }}</td><td>Menghapus pengguna dari router (meminta konfirmasi).</td></tr>
    </tbody>
</table>
<p>Tombol di ujung tiap baris melakukan hal yang sama untuk satu pengguna.</p>

<h2>{{ __('Profiles') }}</h2>
<p>Profil adalah paket: kecepatan, jumlah perangkat, harga, dan masa berlaku. <em>{{ __('Hotspot') }} → {{ __('Profiles') }}</em> membuat dan mengubahnya.</p>
<table>
    <thead><tr><th>Kolom</th><th>Arti</th></tr></thead>
    <tbody>
        <tr><td>{{ __('Name') }}</td><td>Tanpa spasi. Voucher dan pengguna memilih profil dengan nama ini.</td></tr>
        <tr><td>{{ __('Rate limit (upload/download)') }}</td><td>Misalnya <code>2M/5M</code> (2 Mbps upload, 5 Mbps download). Kosong berarti tanpa batas.</td></tr>
        <tr><td>{{ __('Shared users') }}</td><td>Berapa perangkat boleh memakai satu login bersamaan.</td></tr>
        <tr><td>{{ __('Price') }}</td><td>Harga jual voucher profil ini. Dipakai di laporan.</td></tr>
        <tr><td>{{ __('Validity after first login') }}</td><td>Berapa lama voucher berlaku sejak pertama dipakai (menit, jam, atau hari). Kosong berarti tanpa kedaluwarsa.</td></tr>
        <tr><td>{{ __('When expired') }}</td><td>{{ __('Remove the user') }}, atau hanya {{ __('Disable the user') }}.</td></tr>
    </tbody>
</table>
<p>Tekan <strong>{{ __('Edit') }}</strong> pada sebuah baris untuk memuatnya ke form, ubah, lalu simpan. Rate limit dan shared users disimpan di router; harga, masa berlaku, dan aksi kedaluwarsa disimpan di Fluxmon.</p>

<h2>{{ __('Active sessions') }}</h2>
<p><em>{{ __('Hotspot') }} → {{ __('Active sessions') }}</em> menampilkan siapa yang sedang online beserta alamat, MAC, uptime, dan data yang ditransfer. Daftar diperbarui setiap 5 detik dan bisa difilter berdasarkan user, IP, atau MAC. <strong>{{ __('Disconnect') }}</strong> mengakhiri sesi seketika; pengguna bisa login lagi kecuali Anda juga menonaktifkannya.</p>
