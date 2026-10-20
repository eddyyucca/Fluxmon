<p>Peringatan memberi tahu Anda saat ada yang perlu perhatian. Fluxmon mengeluarkan tiga jenis, dan masing-masing mengirim pesan saat mulai dan satu lagi saat selesai.</p>
<table>
    <thead><tr><th>Jenis</th><th>Muncul saat</th></tr></thead>
    <tbody>
        <tr><td>Router offline</td><td>Fluxmon tidak bisa terhubung ke router. Selalu aktif, tanpa pengaturan.</td></tr>
        <tr><td>Pengecekan mati</td><td>Sebuah <a href="{{ route('docs', 'monitoring') }}">pengecekan</a> berhenti menjawab.</td></tr>
        <tr><td>Aturan</td><td>Nilai yang Anda pilih (CPU, memori, suhu) bertahan melewati batas selama waktu tertentu.</td></tr>
    </tbody>
</table>

<h2>{{ __('Rules') }}</h2>
<p>Buka <em>{{ __('Alerts') }}</em> di sidebar. Di bawah <strong>{{ __('New rule') }}</strong> pilih:</p>
<ul>
    <li><strong>{{ __('Metric') }}:</strong> beban CPU, memori terpakai, atau suhu.</li>
    <li><strong>{{ __('Router') }}:</strong> satu router, atau semua router.</li>
    <li><strong>{{ __('Fires when value is') }}</strong> di atas (<code>&gt;</code>) atau di bawah (<code>&lt;</code>) sebuah angka.</li>
    <li><strong>{{ __('For (minutes)') }}:</strong> berapa lama kondisi itu harus bertahan.</li>
</ul>
<p>Contoh: <em>CPU &gt; 90 selama 5 menit</em>. Aturan baru aktif bila <strong>setiap</strong> pembacaan dalam rentang itu melewati batas, jadi lonjakan singkat tidak memicunya. Aturan selesai pada pembacaan pertama yang kembali di dalam batas.</p>
<p><strong>{{ __('Add recommended') }}</strong> membuat tiga aturan untuk semua router: CPU di atas 90% selama 5 menit, memori di atas 90% selama 5 menit, suhu di atas 75 °C selama 5 menit. Menjalankannya lagi tidak membuat duplikat.</p>
<p>Gunakan <strong>{{ __('Pause') }}</strong> untuk mematikan aturan sementara dan ikon tempat sampah untuk menghapusnya.</p>

<h2>Ke mana peringatan dikirim</h2>
<p>Peringatan selalu muncul di dasbor (banner merah) dan di daftar <em>{{ __('Recent events') }}</em>. Agar Anda juga diberi tahu, atur satu atau lebih kanal di <a href="{{ route('docs', 'settings') }}">{{ __('Settings') }}</a>:</p>
<ul>
    <li><strong>Telegram</strong>, dengan <em>{{ __('Send alerts to Telegram') }}</em> dicentang.</li>
    <li><strong>{{ __('Webhook URL') }}:</strong> Fluxmon mengirim <code>POST</code> JSON berisi <code>title</code>, <code>message</code>, <code>router</code>, <code>kind</code>, dan <code>state</code> (<code>firing</code> atau <code>resolved</code>). Berguna untuk aplikasi chat dan otomasi.</li>
    <li><strong>Email</strong> ke satu atau beberapa alamat. Ini butuh pengaturan <code>MAIL_*</code> di <code>.env</code>.</li>
</ul>
<p>Gunakan <em>{{ __('Send test alert') }}</em> di {{ __('Settings') }} untuk menguji setiap kanal. Kanal yang gagal tidak menghentikan kanal lain.</p>

<div class="callout">
    Sebuah peringatan diumumkan sekali. Selama masalah berlangsung Anda tidak akan menerima pesan tiap menit; Anda mendapat satu saat mulai dan satu saat diperbaiki.
</div>
