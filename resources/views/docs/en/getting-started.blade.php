<p>Fluxmon is a web panel for MikroTik routers. It watches your routers, warns you when something breaks, keeps copies of their configuration, and sells hotspot vouchers. One Fluxmon can manage as many routers as you like.</p>

<h2>What you need</h2>
<ul>
    <li>A MikroTik router with the <strong>API</strong> service enabled (and <strong>SSH</strong> if you want configuration backups).</li>
    <li>The router must be reachable from the computer that runs Fluxmon.</li>
    <li>The scheduler running in the background (see <a href="{{ route('docs', 'scheduler') }}">Scheduler</a>). Charts, alerts and voucher sales depend on it.</li>
</ul>

<h2>First steps</h2>
<ol>
    <li><strong>Create the admin account.</strong> The very first visit shows a registration form. After that, registration closes; more accounts are added by an admin in <em>Settings</em>.</li>
    <li><strong>Prepare the router</strong> and <strong>add it</strong> in <em>Routers → Add router</em>. See <a href="{{ route('docs', 'connect-router') }}">Connect a router</a>.</li>
    <li><strong>Press Test.</strong> A green badge with the board name and RouterOS version means Fluxmon can talk to the router.</li>
    <li><strong>Start the scheduler</strong> so the router is sampled every minute.</li>
    <li>Optional: create <a href="{{ route('docs', 'alerts') }}">alert rules</a> and connect Telegram in <a href="{{ route('docs', 'settings') }}">Settings</a>.</li>
</ol>

<h2>How the menu is organised</h2>
<table>
    <thead><tr><th>Menu</th><th>What it is for</th></tr></thead>
    <tbody>
        <tr><td>Dashboard, Reports, Alerts</td><td>Everything across all your routers.</td></tr>
        <tr><td>Router name (Overview, Monitoring, Checks, Logs)</td><td>Watching one router. Pick the router with the selector at the top of the sidebar.</td></tr>
        <tr><td>Hotspot (Users, Profiles, Vouchers, Active sessions)</td><td>Selling and managing hotspot access on that router.</td></tr>
        <tr><td>Maintenance (Backups)</td><td>Saved copies of the router configuration.</td></tr>
        <tr><td>Manage (Routers, Settings, Audit log)</td><td>Administration. You only see what your role allows.</td></tr>
    </tbody>
</table>

<div class="callout">
    <strong>Tip:</strong> the moon/sun button in the top bar switches dark mode, and the <strong>EN | ID</strong> switch next to it changes the language between English and Bahasa Indonesia. Your choice is remembered for your account.
</div>
