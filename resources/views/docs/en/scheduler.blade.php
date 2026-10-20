<p>Some work cannot wait for someone to open a page. Fluxmon does it in the background on a timetable called the <strong>scheduler</strong>. You start it once and leave it running.</p>

<div class="callout-warn">
    <strong>Without the scheduler</strong> the panel still opens and the live pages work, but: monitoring charts stay empty, alerts are never raised, checks do not run, vouchers are not counted as sold or expired until you press <em>Sync</em>, and no nightly backups or daily reports happen.
</div>

<h2>What runs and when</h2>
<table>
    <thead><tr><th>Task</th><th>When</th><th>What it does</th></tr></thead>
    <tbody>
        <tr><td><code>fluxmon:sync</code></td><td>every minute</td><td>For each router: finds used and expired vouchers, records CPU, memory, traffic and other readings, runs checks, evaluates alert rules, and notices when a router goes offline or returns.</td></tr>
        <tr><td><code>fluxmon:rollup</code></td><td>hourly</td><td>Folds minute data into hourly averages and deletes data older than the retention limits.</td></tr>
        <tr><td><code>fluxmon:backup</code></td><td>daily at 03:00</td><td>Saves each router's configuration when it changed.</td></tr>
        <tr><td><code>fluxmon:daily-report</code></td><td>daily at 23:55</td><td>Sends the day's sales to Telegram (if switched on).</td></tr>
    </tbody>
</table>

<h2>Start it</h2>
<h3>While testing, on any computer</h3>
<p>Open a second terminal in the Fluxmon folder and leave it running:</p>
<pre><code>php artisan schedule:work</code></pre>

<h3>On a Linux server (cron)</h3>
<pre><code>* * * * * cd /path/to/fluxmon &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>

<h3>On Windows (Task Scheduler)</h3>
<ol>
    <li>Open <em>Task Scheduler → Create Task</em>.</li>
    <li><em>Triggers:</em> new trigger, daily, repeat every <strong>1 minute</strong> indefinitely.</li>
    <li><em>Actions:</em> start a program: <code>php.exe</code> with arguments <code>artisan schedule:run</code>, and <em>Start in</em> set to the Fluxmon folder.</li>
    <li>Tick <em>Run whether user is logged on or not</em>.</li>
</ol>

<h2>Run a task by hand</h2>
<pre><code>php artisan fluxmon:sync
php artisan fluxmon:backup
php artisan fluxmon:daily-report --force</code></pre>
<p>Each prints what it did for every router, which is also a quick way to see why something fails.</p>
