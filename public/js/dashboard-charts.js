// dashboard-charts.js
// Requires Chart.js to be loaded before this file.
// window.dashboardData must be set by dashboard.blade.php before DOMContentLoaded fires.

document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('dashWeekChart') || !window.dashboardData) return;

    const { days, weekCounts } = window.dashboardData;

    new Chart(document.getElementById('dashWeekChart'), {
        type: 'bar',
        data: {
            labels: days,
            datasets: [{
                data: weekCounts,
                backgroundColor: days.map((_, i) => i === days.length - 1 ? '#4A9EE8' : '#1B6FC8'),
                borderRadius: 4,
                borderSkipped: 'bottom',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => c.parsed.y + ' builds' } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#869FB1', font: { size: 11 } }, border: { color: '#1B3A6B' } },
                y: { grid: { color: '#1B3A6B' }, ticks: { color: '#869FB1', font: { size: 11 }, stepSize: 2 }, border: { display: false }, min: 0 }
            }
        }
    });
});
