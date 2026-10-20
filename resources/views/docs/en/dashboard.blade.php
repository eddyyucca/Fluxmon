<h2>Dashboard</h2>
<p>The dashboard summarises every router your account may see.</p>
<ul>
    <li><strong>Revenue cards</strong> (superadmin only): company-wide revenue and vouchers sold today and this month.</li>
    <li><strong>Revenue chart</strong> (superadmin only): the last 14 days. <em>Full report</em> opens <a href="{{ route('docs', 'reports') }}">Reports</a>.</li>
    <li><strong>Router cards:</strong> Online or Offline, board and RouterOS version, uptime, users online now, hotspot users, and CPU and memory bars. They refresh every 10 seconds.</li>
    <li><strong>Locked devices:</strong> staff see <em>Sign in to this device</em> on a device they have not entered yet. Enter that device's account to see its live stats (see <a href="{{ route('docs', 'accounts') }}">Accounts and roles</a>).</li>
    <li><strong>Red banners</strong> appear when an <a href="{{ route('docs', 'alerts') }}">alert</a> is firing. Click one to open the alerts page.</li>
    <li><strong>Recent activity:</strong> the latest actions by admins (visible only to roles that may see the audit log).</li>
</ul>
<div class="callout">
    Revenue comes from Fluxmon's own records, so it still shows when a router is offline. Router cards need a live connection.
</div>

<h2>Router overview</h2>
<p>Pick a router in the selector at the top of the sidebar, or click its card. The <em>Overview</em> page shows:</p>
<ul>
    <li><strong>Badges</strong> with the router identity, RouterOS version, architecture and detected features.</li>
    <li><strong>Online now / Hotspot users</strong> and <strong>revenue</strong> for today and this month.</li>
    <li><strong>Live traffic:</strong> download and upload for one interface, updated every 2 seconds. Choose the interface in the drop-down. The chart keeps the last 40 readings and is not stored; for history use <a href="{{ route('docs', 'monitoring') }}">Monitoring</a>.</li>
    <li><strong>System:</strong> CPU and memory.</li>
    <li><strong>Sync</strong> (top right): checks the router right now for vouchers that were used or expired, instead of waiting for the next minute.</li>
</ul>
