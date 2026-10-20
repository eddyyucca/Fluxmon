<h2>The connection test fails</h2>
<p>The message under the router explains the cause. The usual ones:</p>
<table>
    <thead><tr><th>Message or symptom</th><th>Check</th></tr></thead>
    <tbody>
        <tr><td>Timeout, connection refused, "unable to connect"</td><td>Is <code>/ip service api</code> enabled? Is the address and port right? Does a firewall or the service's <em>address</em> restriction block the Fluxmon computer? Can you <code>ping</code> the router from that computer?</td></tr>
        <tr><td>"Invalid user name or password"</td><td>Retype the account and password. Edit the router; leave the password empty only if you want to keep the old one.</td></tr>
        <tr><td>It works, but some pages say a command is not allowed</td><td>The account's group lacks a policy. Add <code>write</code> for hotspot changes, <code>test</code> for ping checks.</td></tr>
        <tr><td>API-SSL does not connect</td><td>Tick <em>Use API-SSL</em> <strong>and</strong> set the API port to 8729; the router needs a certificate assigned to the <code>api-ssl</code> service.</td></tr>
    </tbody>
</table>

<h2>Monitoring charts are empty</h2>
<p>Start the <a href="{{ route('docs', 'scheduler') }}">scheduler</a>. Points appear one per minute; traffic needs two samples. Check with <code>php artisan fluxmon:sync</code>, which should print <em>online</em> for each router.</p>

<h2>Vouchers are not counted, or do not expire</h2>
<ul>
    <li>Press <strong>Sync</strong> on the router's Overview. If the voucher is now counted, the scheduler is not running.</li>
    <li>A voucher counts only after it has been used at least once. A card nobody has logged in with is not a sale.</li>
    <li>Only vouchers made with the <em>Generate</em> form are tracked; hand-made hotspot users are not.</li>
</ul>

<h2>Backups fail</h2>
<ul>
    <li><strong>SSH login failed:</strong> enable <code>/ip service ssh</code>, check the SSH port in the router settings, and make sure the account has the <code>ssh</code> policy.</li>
    <li><strong>"host key changed":</strong> see <a href="{{ route('docs', 'backups') }}">Configuration backups</a>. Clear the key only if the router was replaced.</li>
    <li><strong>"Empty export":</strong> the account may lack <code>read</code> or <code>policy</code> rights.</li>
</ul>

<h2>Telegram messages do not arrive</h2>
<ul>
    <li>Press <em>Send test message</em>; the error from Telegram is shown.</li>
    <li>Did you send your bot a message first? A bot cannot start a conversation.</li>
    <li>Is the chat ID right? For groups it usually starts with <code>-</code>.</li>
    <li>For alerts, is <em>Send alerts to Telegram</em> ticked? Daily reports have their own checkbox.</li>
    <li>Can the Fluxmon computer reach <code>api.telegram.org</code> from its network?</li>
</ul>

<h2>A menu or page is missing</h2>
<p>Your role does not include it (see <a href="{{ route('docs', 'accounts') }}">Accounts and roles</a>). Staff only get the menus their <em>device account</em> allows, and only after signing in to the device. Ask a superadmin or administrator.</p>

<h2>I cannot sign in to a device</h2>
<ul>
    <li>Use the <strong>device account</strong> of that device, not your Fluxmon login. Every device has its own.</li>
    <li>The username and password must belong to <em>this</em> device; an account made for another device does not work here.</li>
    <li>After 5 wrong attempts within a minute the device is locked for you for a short while. Wait and try again.</li>
    <li>The account may be switched off. A superadmin or administrator can check it under <em>Device accounts</em> on that device and set a new password.</li>
    <li>If the device does not appear for you at all, your Fluxmon account is limited to other devices.</li>
    <li>Signed in but sent back to the sign-in page? Device sign-ins end after 8 idle hours.</li>
</ul>

<h2>I forgot the admin password</h2>
<p>Someone with access to the server can set a new one. In the Fluxmon folder run:</p>
<pre><code>php artisan tinker --execute="App\Models\User::where('email', 'you@example.com')->first()->update(['password' => 'a-new-password']);"</code></pre>
<p>Then sign in and change it from your account page.</p>

<h2>After updating Fluxmon something breaks</h2>
<pre><code>git pull
composer install
php artisan migrate
npm install &amp;&amp; npm run build</code></pre>
<div class="callout-warn">
    Always use <code>php artisan migrate</code> to update the database. <code>migrate:fresh</code> erases all data.
</div>

<h2>Still stuck?</h2>
<p>Note the exact message and your RouterOS version and board (shown on the router's Overview), then contact <a href="mailto:{{ config('fluxmon.developer.email') }}">{{ config('fluxmon.developer.email') }}</a>.</p>
