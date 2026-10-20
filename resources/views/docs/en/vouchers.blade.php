<p>Vouchers are prepaid logins. You generate a batch, print the cards, and sell them. Fluxmon notices when each one is first used and counts it as a sale.</p>

<h2>Generate a batch</h2>
<p>Open <em>Hotspot → Vouchers</em> and fill in the form on the left. First create the <a href="{{ route('docs', 'hotspot') }}">profile</a> that sets the price and validity.</p>
<table>
    <thead><tr><th>Field</th><th>Meaning</th></tr></thead>
    <tbody>
        <tr><td>Profile</td><td>Decides speed, price and validity. The profile price is shown under the field.</td></tr>
        <tr><td>Quantity</td><td>1 to 500 per batch.</td></tr>
        <tr><td>Code length</td><td>3 to 16 characters. Six is a good balance.</td></tr>
        <tr><td>Prefix</td><td>Optional, up to 8 letters, digits, <code>-</code> or <code>_</code>, put in front of every code.</td></tr>
        <tr><td>Characters</td><td>Digits, lowercase, uppercase, or lowercase + digits. Look-alike characters (0/o, 1/l/i) are left out so cards are easy to read.</td></tr>
        <tr><td>Login type</td><td><strong>Code only</strong>: the code is both username and password. <strong>User + password</strong>: two different codes.</td></tr>
        <tr><td>Online time limit</td><td>Optional. Total time the voucher may be online, enforced by the router.</td></tr>
        <tr><td>Data limit</td><td>Optional, in MB.</td></tr>
        <tr><td>Price override</td><td>Optional. Uses this price instead of the profile's for this batch.</td></tr>
    </tbody>
</table>
<p>Press <strong>Generate</strong>. The users are created on the router and the batch page opens. If something fails halfway, the vouchers created so far are kept and the error is shown.</p>

<h2>Print</h2>
<p>On a batch, press <strong>Print</strong>. A sheet opens in a new tab with one card per voucher: hotspot name, price, code (or user and password), profile and validity.</p>
<ul>
    <li><strong>Size:</strong> normal (3 per row) or small (5 per row), laid out for A4.</li>
    <li><strong>QR code:</strong> scanning it opens the hotspot login page with the credentials filled in. The address comes from the router's <em>DNS name / login address</em>.</li>
    <li><strong>Unused only:</strong> on by default, so you never reprint cards that were already sold.</li>
    <li>Press the <strong>Print</strong> button on the sheet. The cards have dashed borders for cutting.</li>
</ul>
<p><strong>CSV</strong> on the batch page downloads the codes for use in other tools.</p>

<h2>When is a voucher "sold"?</h2>
<ol>
    <li>Every minute, Fluxmon checks the router. The first time a voucher shows any usage, it becomes <strong>Used</strong> and counts as <strong>revenue</strong> at the price set when the batch was made.</li>
    <li>If the profile has a validity, the countdown starts at that moment.</li>
    <li>When the validity ends, Fluxmon disconnects the user and removes or disables it (as set in the profile) and marks the voucher <strong>Expired</strong>.</li>
</ol>
<div class="callout-warn">
    Detection and expiry run in the background. Without the scheduler (<a href="{{ route('docs', 'scheduler') }}">Scheduler</a>) nothing is counted until you press <strong>Sync</strong>, and expired vouchers stay active.
</div>
<p><strong>Online time limit</strong> and <strong>validity</strong> are different. The time limit counts only the minutes a voucher is actually online and is enforced by the router. Validity is calendar time from the first login and is handled by Fluxmon.</p>

<h2>Delete a batch</h2>
<p>The trash icon removes the batch's <strong>unused</strong> vouchers from the router and from Fluxmon. Vouchers that were already used stay in the records so your reports remain correct.</p>
<p>If you delete a voucher directly on the router, Fluxmon notices at the next sync: an unused one is simply forgotten, a used one stays counted in reports and is marked expired.</p>
