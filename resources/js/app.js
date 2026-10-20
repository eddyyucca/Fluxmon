import './bootstrap';
import './charts';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/** Format a number as money with the separators of the interface language (1.000 in Indonesian, 1,000 in English). */
window.fmtMoney = (n, currency = 'Rp') => {
    const locale = document.documentElement.lang === 'id' ? 'id-ID' : 'en-US';

    return `${currency} ${Math.round(n).toLocaleString(locale)}`.trim();
};

/** bits/s -> "1.2 Mbps" */
window.fmtBits = (bps) => {
    const units = ['bps', 'Kbps', 'Mbps', 'Gbps'];
    let i = 0;
    while (bps >= 1000 && i < units.length - 1) {
        bps /= 1000;
        i++;
    }
    return `${bps.toFixed(i ? 1 : 0)} ${units[i]}`;
};

window.fmtBytes = (b) => {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    while (b >= 1024 && i < units.length - 1) {
        b /= 1024;
        i++;
    }
    return `${b.toFixed(i ? 1 : 0)} ${units[i]}`;
};

Alpine.start();
