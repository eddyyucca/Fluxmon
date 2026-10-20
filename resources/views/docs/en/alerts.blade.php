<p>An alert tells you when something needs attention. Fluxmon raises three kinds, and each one sends a message when it starts and another when it clears.</p>
<table>
    <thead><tr><th>Kind</th><th>Raised when</th></tr></thead>
    <tbody>
        <tr><td>Router offline</td><td>Fluxmon cannot connect to the router. Always on, no setup needed.</td></tr>
        <tr><td>Check down</td><td>A <a href="{{ route('docs', 'monitoring') }}">check</a> stops answering.</td></tr>
        <tr><td>Rule</td><td>A value you chose (CPU, memory, temperature) stays beyond a limit for a set time.</td></tr>
    </tbody>
</table>

<h2>Rules</h2>
<p>Open <em>Alerts</em> in the sidebar. Under <strong>New rule</strong> choose:</p>
<ul>
    <li><strong>Metric:</strong> CPU load, memory used or temperature.</li>
    <li><strong>Router:</strong> one router, or all routers.</li>
    <li><strong>Fires when value is</strong> above (<code>&gt;</code>) or below (<code>&lt;</code>) a number.</li>
    <li><strong>For (minutes):</strong> how long it must stay that way.</li>
</ul>
<p>Example: <em>CPU &gt; 90 for 5 minutes</em>. A rule fires only when <strong>every</strong> reading in that window is over the limit, so a short spike does not trigger it. It clears on the first reading back inside the limit.</p>
<p><strong>Add recommended</strong> creates three rules for all routers: CPU above 90% for 5 minutes, memory above 90% for 5 minutes, temperature above 75 °C for 5 minutes. Running it again does not create duplicates.</p>
<p>Use <strong>Pause</strong> to switch a rule off temporarily and the trash icon to delete it.</p>

<h2>Where alerts go</h2>
<p>Alerts always appear on the dashboard (red banners) and in the <em>Recent events</em> list. To also be notified, set up one or more channels in <a href="{{ route('docs', 'settings') }}">Settings</a>:</p>
<ul>
    <li><strong>Telegram</strong>, with <em>Send alerts to Telegram</em> ticked.</li>
    <li><strong>Webhook:</strong> Fluxmon sends a JSON <code>POST</code> with <code>title</code>, <code>message</code>, <code>router</code>, <code>kind</code> and <code>state</code> (<code>firing</code> or <code>resolved</code>). Handy for chat tools and automation.</li>
    <li><strong>Email</strong> to one or more addresses. This needs the <code>MAIL_*</code> settings in <code>.env</code>.</li>
</ul>
<p>Use <em>Send test alert</em> in Settings to check every channel. A channel that fails does not stop the others.</p>

<div class="callout">
    An alert is announced once. While a problem lasts you will not get a message every minute; you get one when it starts and one when it is fixed.
</div>
