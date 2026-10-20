<h2>Monitoring charts</h2>
<p><em>Router → Monitoring</em> shows history. Every minute the scheduler records CPU, memory, temperature (when the board has a sensor), voltage, users online and the traffic of every interface.</p>
<ul>
    <li>Choose a period: <strong>1h, 6h, 24h, 7d or 30d</strong>.</li>
    <li><strong>CPU / Memory</strong> in percent, <strong>Online users</strong>, <strong>Traffic</strong> (pick the interface), and <strong>Temperature</strong> if available.</li>
    <li>The table at the bottom lists each interface with the rate at the last sample.</li>
</ul>
<div class="callout">
    <strong>No data?</strong> Charts fill up only while the scheduler runs. Start it as described in <a href="{{ route('docs', 'scheduler') }}">Scheduler</a>; the first points appear within two minutes (traffic needs two samples to calculate a rate).
</div>

<h3>How long data is kept</h3>
<p>Minute-level samples are kept for 7 days, then folded into hourly averages that are kept for 180 days. Charts of a day or less use the minute data; longer periods use the hourly averages. Both limits can be changed with <code>FLUXMON_METRICS_RAW_DAYS</code> and <code>FLUXMON_METRICS_ROLLUP_DAYS</code> in <code>.env</code>.</p>

<h2>Checks</h2>
<p><em>Router → Checks</em> tests every minute whether something can be reached, and raises an alert when it stops answering.</p>
<table>
    <thead><tr><th>Type</th><th>How it works</th><th>Use it for</th></tr></thead>
    <tbody>
        <tr><td>Ping from router</td><td>The router itself pings the address (2 packets).</td><td>"Can this router reach its ISP gateway or DNS server?"</td></tr>
        <tr><td>TCP port from Fluxmon</td><td>The Fluxmon server tries to open the port (3 second limit).</td><td>"Is my server or the router's service reachable from here?"</td></tr>
    </tbody>
</table>
<ol>
    <li>Fill in a name, choose the type, enter the address (and the port for TCP) and press <strong>Add check</strong>.</li>
    <li>The first result appears after the next minute. The table shows Up/Down, latency and when it was last checked.</li>
    <li><strong>Pause</strong> stops a check without deleting it. The trash icon removes it.</li>
</ol>
<p>When a check goes down an alert fires once, and another message is sent when it recovers. See <a href="{{ route('docs', 'alerts') }}">Alerts</a>.</p>

<h2>Logs</h2>
<p><em>Router → Logs</em> reads the router's own log. The newest entry is first and the page shows up to 300 of the last 1000 lines.</p>
<ul>
    <li><strong>Topic</strong> filters by log topic (system, error, wireless, …); only topics present in the log are listed.</li>
    <li><strong>Search</strong> filters by text in the message.</li>
    <li><strong>Auto refresh</strong> reloads every 5 seconds. Errors and warnings are highlighted.</li>
</ul>
<p>The log comes straight from the router, so it only goes as far back as the router keeps it (usually a few hundred lines in memory).</p>
