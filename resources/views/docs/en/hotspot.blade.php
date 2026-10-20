<h2>Users</h2>
<p><em>Hotspot → Users</em> lists the hotspot users on the router, 50 per page.</p>
<ul>
    <li><strong>Filter</strong> by name or comment, by profile, or by status: <em>Online</em>, <em>Disabled</em> or <em>Never used</em>.</li>
    <li>Each row shows the profile, status, uptime and data used (with the limit, if any). Vouchers you generate show up here too.</li>
</ul>

<h3>Add a user</h3>
<p>Press <strong>Add user</strong>, then enter a username (no spaces), password and profile. Optionally set an <strong>online time limit</strong> and a <strong>data limit in MB</strong>; the router enforces both. The comment is free text.</p>

<h3>Change several users at once</h3>
<p>Tick the users (the box in the header selects the whole page), choose an action and press <strong>Apply</strong>:</p>
<table>
    <thead><tr><th>Action</th><th>Effect</th></tr></thead>
    <tbody>
        <tr><td>Enable / Disable</td><td>Allows or blocks login without deleting the user.</td></tr>
        <tr><td>Reset counters</td><td>Sets uptime and data used back to zero.</td></tr>
        <tr><td>Delete</td><td>Removes the user from the router (asks for confirmation).</td></tr>
    </tbody>
</table>
<p>The buttons at the end of each row do the same for one user.</p>

<h2>Profiles</h2>
<p>A profile is a package: speed, how many devices, price and validity. <em>Hotspot → Profiles</em> creates and edits them.</p>
<table>
    <thead><tr><th>Field</th><th>Meaning</th></tr></thead>
    <tbody>
        <tr><td>Name</td><td>No spaces. Vouchers and users pick a profile by this name.</td></tr>
        <tr><td>Rate limit</td><td>Upload/download, for example <code>2M/5M</code> (2 Mbps up, 5 Mbps down). Empty means unlimited.</td></tr>
        <tr><td>Shared users</td><td>How many devices may use one login at the same time.</td></tr>
        <tr><td>Price</td><td>What a voucher of this profile sells for. Used in reports.</td></tr>
        <tr><td>Validity after first login</td><td>How long the voucher works once it is first used (minutes, hours or days). Empty means no expiry.</td></tr>
        <tr><td>When expired</td><td>Remove the user, or only disable it.</td></tr>
    </tbody>
</table>
<p>Press <strong>Edit</strong> on a row to load it into the form, change it and save. Rate limit and shared users are stored on the router; price, validity and the expiry action are stored in Fluxmon.</p>

<h2>Active sessions</h2>
<p><em>Hotspot → Active sessions</em> lists who is online right now with address, MAC, uptime and data transferred. The list refreshes every 5 seconds and can be filtered by user, IP or MAC. <strong>Disconnect</strong> ends a session immediately; the user can log in again unless you also disable them.</p>
