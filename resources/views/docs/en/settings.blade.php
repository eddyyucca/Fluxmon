<p><em>Manage → Settings</em> is available to admins.</p>

<h2>Language</h2>
<p>Choose English or Bahasa Indonesia. It applies to everyone who uses this Fluxmon, including this documentation.</p>

<h2>Telegram</h2>
<p>Telegram delivers the daily sales report and your alerts to a chat or group.</p>
<ol>
    <li>In Telegram, talk to <strong>@@BotFather</strong>, send <code>/newbot</code> and follow the steps. It gives you a <strong>bot token</strong>.</li>
    <li>Open your new bot and send it any message (or add it to a group and write there). The bot cannot message you before you write to it.</li>
    <li>Find your <strong>chat ID</strong> (for example with a bot such as @@userinfobot, or from the <code>getUpdates</code> page of your bot).</li>
    <li>Enter the token and chat ID in Fluxmon and tick what you want:
        <ul>
            <li><strong>Daily report at 23:55:</strong> revenue and vouchers sold today, per router.</li>
            <li><strong>Send alerts to Telegram:</strong> router offline, failed checks, and threshold rules.</li>
        </ul>
    </li>
    <li>Press <strong>Save</strong>, then <strong>Send test message</strong>.</li>
</ol>
<p>The token is stored encrypted. Leave the token field empty when saving to keep the stored one.</p>

<h2>Other alert channels</h2>
<ul>
    <li><strong>Webhook URL:</strong> receives a JSON <code>POST</code> for every alert (see <a href="{{ route('docs', 'alerts') }}">Alerts</a>).</li>
    <li><strong>Email recipients:</strong> one or more addresses separated by commas. Sending needs the mail settings (<code>MAIL_MAILER</code>, <code>MAIL_HOST</code>, <code>MAIL_USERNAME</code>… ) in the <code>.env</code> file. Until those are set, mail only goes to the log file.</li>
</ul>
<p><strong>Send test alert</strong> sends a message to every configured channel at once.</p>

<h2>Accounts</h2>
<p>See <a href="{{ route('docs', 'accounts') }}">Accounts and roles</a>.</p>

<h2>Settings that live in .env</h2>
<p>A few options are set in the <code>.env</code> file next to the application, then take effect after the app restarts:</p>
<table>
    <thead><tr><th>Variable</th><th>Default</th><th>Meaning</th></tr></thead>
    <tbody>
        <tr><td><code>APP_TIMEZONE</code></td><td>Asia/Jakarta</td><td>Timezone for reports and schedules.</td></tr>
        <tr><td><code>FLUXMON_METRICS_RAW_DAYS</code></td><td>7</td><td>Days of minute-level monitoring data.</td></tr>
        <tr><td><code>FLUXMON_METRICS_ROLLUP_DAYS</code></td><td>180</td><td>Days of hourly averages.</td></tr>
        <tr><td><code>FLUXMON_BACKUPS_KEEP</code></td><td>30</td><td>Configuration backups kept per router.</td></tr>
        <tr><td><code>MAIL_*</code></td><td>log</td><td>Outgoing email for alerts.</td></tr>
    </tbody>
</table>
