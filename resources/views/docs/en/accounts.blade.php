<p>Fluxmon has <strong>two levels of sign-in</strong>. Everyone signs in to Fluxmon first. People who manage a device then sign in <strong>again, to that device</strong>, with an account that belongs to that device only. Superadmins and administrators skip the second step.</p>

<h2>Fluxmon accounts</h2>
<p>Admins create them in <em>Settings → Accounts</em>. The role decides how far the account reaches.</p>
<table>
    <thead><tr><th>Role</th><th>Second login</th><th>Can do</th></tr></thead>
    <tbody>
        <tr><td><strong>Superadmin</strong></td><td>Never</td><td>Everything: every device, <strong>one combined report of all devices</strong>, routers, Settings, Fluxmon accounts, device accounts, audit log.</td></tr>
        <tr><td><strong>Administrator</strong></td><td>Never</td><td>Every device, one device at a time: monitoring, hotspot, vouchers, backups, alerts, reports of a single device, device accounts, audit log. Cannot open Settings, add routers, manage Fluxmon accounts or see the combined report.</td></tr>
        <tr><td><strong>Staff</strong></td><td>For every device</td><td>Nothing on their own. They sign in to a device with that device's account, and what they can do there depends on that account's role.</td></tr>
    </tbody>
</table>
<p>The first account, created when Fluxmon starts for the first time, is a superadmin. You cannot change your own role or delete your own account, so there is always one left.</p>

<h2>Device accounts</h2>
<p>Every device has its own management accounts, separate from Fluxmon accounts. A device account works for <strong>that device only</strong>: the same person needs another account to enter a second device.</p>
<table>
    <thead><tr><th>Device role</th><th>Meant for</th><th>Can do on that device</th></tr></thead>
    <tbody>
        <tr><td><strong>Technician</strong></td><td>Network staff</td><td>Monitoring, hotspot users and profiles, vouchers, reports, backups, alerts and checks. Cannot manage device accounts.</td></tr>
        <tr><td><strong>Cashier</strong></td><td>Counter staff</td><td>Sell vouchers, manage hotspot users, see reports and basic monitoring. Cannot edit profiles or see backups.</td></tr>
        <tr><td><strong>Viewer</strong></td><td>Someone who only watches</td><td>Read-only: monitoring, users and the report of that device.</td></tr>
    </tbody>
</table>

<h3>Create device accounts</h3>
<ol>
    <li>Open the device and choose <em>Maintenance → Device accounts</em> (superadmins and administrators).</li>
    <li>Enter a name, a username (letters, digits, <code>.</code> <code>_</code> <code>-</code>), a password of at least 8 characters and the role.</li>
    <li>Press <strong>Add account</strong> and give the credentials to the person.</li>
</ol>
<p>To change a role, set a new password or switch an account off, press <strong>Edit</strong> on its row. Changes take effect on the person's next click: a disabled or removed account is signed out immediately.</p>

<h2>How staff sign in</h2>
<ol>
    <li>Sign in to Fluxmon with a Fluxmon account whose role is <strong>Staff</strong>.</li>
    <li>On the dashboard, devices they have not entered yet show <strong>Sign in to this device</strong>. Opening a device from the menu does the same.</li>
    <li>Enter the device username and password. On success the device opens, and the top bar shows the device account in use with <strong>Sign out of device</strong>.</li>
</ol>
<ul>
    <li><strong>Each device separately:</strong> signing in to one device does not open another.</li>
    <li><strong>Idle limit:</strong> a device sign-in ends after 8 hours without activity (<code>FLUXMON_DEVICE_SESSION_MINUTES</code> in <code>.env</code>). Pages that refresh themselves in the background do not count as activity.</li>
    <li><strong>Wrong passwords:</strong> after 5 failed attempts within a minute that device is locked for that person for a short while. Other devices are not affected.</li>
    <li>Signing out of Fluxmon ends all device sign-ins.</li>
</ul>

<h3>Which devices can staff see?</h3>
<p>In <em>Settings → Accounts</em> a Staff account can be limited to some devices (<em>Only some routers</em>). Other devices do not exist for that person, even if they know a device password. Without a limit, a Staff account may try every device.</p>

<h2>Reports and the dashboard</h2>
<ul>
    <li><strong>Superadmin:</strong> the dashboard shows the company-wide revenue, and Reports can combine <em>All routers</em> into one report.</li>
    <li><strong>Administrator and Staff:</strong> reports are always for one device (Staff only for devices they are signed in to), and the company-wide totals are not shown.</li>
</ul>

<h2>Audit log</h2>
<p><em>Manage → Audit log</em> (superadmins and administrators) records who did what, when and from which address. Actions by staff also name the <strong>device account</strong> used, for example <em>user.created carol [device account: Rina]</em>, and device sign-ins and failed attempts are listed too.</p>
