<p>Fluxmon talks to a router through the RouterOS <strong>API</strong> (monitoring, hotspot) and over <strong>SSH</strong> (configuration backups). Both are switched off or limited by default on some routers, so prepare the router first.</p>

<h2>1. Enable the services on the router</h2>
<p>In Winbox choose <em>New Terminal</em> and run:</p>
<pre><code>/ip service enable api
/ip service enable ssh</code></pre>
<p>The API listens on port <code>8728</code> and the secure API-SSL on <code>8729</code>. SSH uses port <code>22</code>.</p>

<h2>2. Create a dedicated account</h2>
<p>Do not give Fluxmon your everyday <code>admin</code> login. Create a separate account so you can remove it at any time:</p>
<pre><code>/user group add name=fluxmon policy=read,write,api,ssh,test
/user add name=fluxmon group=fluxmon password=&lt;a strong password&gt;</code></pre>
<ul>
    <li><code>read</code> and <code>api</code> are enough for monitoring.</li>
    <li><code>write</code> is needed to manage hotspot users, profiles and vouchers.</li>
    <li><code>ssh</code> is needed for backups, <code>test</code> for ping checks.</li>
</ul>
<div class="callout-warn">
    RouterOS versions differ slightly in which policy a command needs. If something is refused, the error message appears in Fluxmon; add the missing policy to the group.
</div>

<h2>3. Limit who can reach the services (recommended)</h2>
<pre><code>/ip service set api address=192.168.88.0/24
/ip service set ssh address=192.168.88.0/24</code></pre>
<p>Use the address of the computer or network that runs Fluxmon. Make sure no firewall rule blocks ports 8728 and 22 from that computer.</p>

<h2>4. Add the router in Fluxmon</h2>
<p>Open <em>Routers → Add router</em> (admins only) and fill in:</p>
<table>
    <thead><tr><th>Field</th><th>Meaning</th></tr></thead>
    <tbody>
        <tr><td>Name</td><td>Any label you like; shown in menus and reports.</td></tr>
        <tr><td>Host / IP</td><td>The router's address as seen from Fluxmon.</td></tr>
        <tr><td>API port / SSH port</td><td>Usually 8728 and 22. For API-SSL use 8729 and tick <em>Use API-SSL</em>.</td></tr>
        <tr><td>Username / Password</td><td>The account from step 2. The password is stored encrypted.</td></tr>
        <tr><td>Hotspot name</td><td>Printed on vouchers.</td></tr>
        <tr><td>DNS name / login address</td><td>Where customers open the hotspot login page (for example <code>hotspot.local</code>). Used in voucher QR codes; the router address is used if left empty.</td></tr>
        <tr><td>Currency</td><td>Shown next to prices, for example <code>Rp</code>.</td></tr>
        <tr><td>Default traffic interface</td><td>The interface shown first in the live traffic chart.</td></tr>
        <tr><td>Timeout</td><td>Seconds to wait for the router before giving up.</td></tr>
    </tbody>
</table>

<h2>5. Test the connection</h2>
<p>On the router list press <strong>Test</strong>. On success you see the board name and version, and Fluxmon records which features the router supports (RouterOS 6 or 7, WireGuard, new wifi package, LTE…). These appear as badges on the router's Overview page.</p>
<p>If the test fails, the message tells you why. See <a href="{{ route('docs', 'troubleshooting') }}">Troubleshooting</a>.</p>
