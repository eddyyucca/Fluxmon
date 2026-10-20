import {
    Chart,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Filler,
} from 'chart.js';

Chart.register(BarController, BarElement, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Filler);

Chart.defaults.font.family = 'Inter, Figtree, system-ui, sans-serif';
Chart.defaults.font.size = 11;
Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.18)';
Chart.defaults.plugins.legend = { display: false };
Chart.defaults.maintainAspectRatio = false;

window.Chart = Chart;
