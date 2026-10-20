<p><em>Reports</em> (sidebar) shows what you earned from vouchers. A voucher counts as sold at the moment of its <strong>first login</strong>, at the price fixed when its batch was generated. Vouchers that were never used are not revenue.</p>

<h2>Choose what to see</h2>
<ul>
    <li><strong>Period:</strong> today, last 7 days, last 30 days, this month, last month, or a custom range (pick <em>From</em> and <em>To</em>).</li>
    <li><strong>Router:</strong> one router or all of them. Accounts limited to certain routers only see those.</li>
</ul>
<p>Press <strong>Apply</strong> to refresh.</p>

<h2>What the page shows</h2>
<ul>
    <li><strong>Cards:</strong> revenue, vouchers sold, average price, average revenue per day.</li>
    <li><strong>Revenue per day:</strong> a bar chart; hover a bar for the amount and number of vouchers.</li>
    <li><strong>By profile</strong> and <strong>By router:</strong> which packages and which sites earn the most.</li>
</ul>

<h2>Export</h2>
<p>The <strong>CSV</strong> button downloads every sold voucher in the chosen period and router: time sold, router, username, profile and price. Open it in Excel or Google Sheets for your own bookkeeping.</p>

<h2>Why a number looks wrong</h2>
<ul>
    <li><strong>Today's sales are missing.</strong> Figures update when Fluxmon syncs with the router. Press <em>Sync</em> on the router's page, or start the <a href="{{ route('docs', 'scheduler') }}">scheduler</a>.</li>
    <li><strong>A router shows 0.</strong> Only vouchers generated in Fluxmon are counted. Users you created by hand are not sales.</li>
    <li><strong>Mixed currencies.</strong> Totals add the numbers as they are. If routers use different currencies, report each router separately.</li>
    <li><strong>Times.</strong> Days follow the timezone set by <code>APP_TIMEZONE</code> in <code>.env</code> (default <code>Asia/Jakarta</code>).</li>
</ul>
