import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

// Export globally for the inline Twig script
window.Chart = Chart;
