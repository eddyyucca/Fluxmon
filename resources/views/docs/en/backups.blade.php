<p>A backup is a copy of the router's configuration (the output of <code>/export</code>), saved in Fluxmon. If a router is reset or someone changes something by mistake, you can see exactly what it looked like before.</p>

<h2>Before you start</h2>
<ul>
    <li>The <strong>SSH</strong> service must be enabled on the router, and Fluxmon needs the SSH port (default 22) in the router settings.</li>
    <li>The router account must be allowed to log in over SSH (the <code>ssh</code> policy, see <a href="{{ route('docs', 'connect-router') }}">Connect a router</a>).</li>
</ul>

<h2>Take and read backups</h2>
<p>Open <em>Maintenance → Backups</em> on a router.</p>
<ul>
    <li><strong>Back up now</strong> saves the current configuration.</li>
    <li>Fluxmon also backs up <strong>every router each night at 03:00</strong> (needs the <a href="{{ route('docs', 'scheduler') }}">scheduler</a>).</li>
    <li>A new version is stored <strong>only when something changed</strong>. If nothing changed you are told so, and no duplicate is made.</li>
    <li>The newest 30 backups per router are kept; older ones are deleted automatically (<code>FLUXMON_BACKUPS_KEEP</code>).</li>
</ul>
<p>Each row shows when it was saved, whether it was <em>manual</em> or <em>scheduled</em>, who started it, and its size.</p>

<h2>See what changed</h2>
<p>Click a backup (or <strong>Changes</strong>). It opens as a comparison with the previous version: lines added are green with <code>+</code>, lines removed are red with <code>−</code>, and a little unchanged context is shown around them. Use <strong>Compare with</strong> to pick any other version. The oldest backup has nothing to compare with and shows the full configuration.</p>
<p><strong>.rsc</strong> downloads the backup as a RouterOS script file.</p>

<h2>Passwords and secrets</h2>
<p>RouterOS 7 leaves sensitive values such as passwords out of an export by default, so Fluxmon's backups do not contain them. Keep your own record of router passwords and keys; a backup alone does not restore them.</p>

<h2>Security of the SSH connection</h2>
<p>The first time Fluxmon connects over SSH it remembers the router's <strong>host key</strong>. If the key later changes, backups stop with a warning, because that can mean someone is intercepting the connection. If you really did reinstall or replace the router, an admin can press <strong>Clear</strong> next to <em>SSH host key pinned</em> at the bottom of the Backups page; the next backup pins the new key.</p>
<div class="callout-warn">
    Do not clear the key unless you know why it changed.
</div>
