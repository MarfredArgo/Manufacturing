(function () {
    if (!document.getElementById('statusChart') || !window.reportsData) return;

    const {
        statusLabels, statusCounts, statusColors,
        weekLabels, weekBuilds, weekDefects,
        partsReady, partsSourcing, partsMissing
    } = window.reportsData;

    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: statusLabels,
            datasets: [{ data: statusCounts, backgroundColor: statusColors, borderRadius: 4, borderSkipped: 'bottom' }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#5B7A9D', font: { size: 11 } }, border: { color: '#1B3A6B' } },
                y: { grid: { color: '#E2E8F0' }, ticks: { color: '#5B7A9D', font: { size: 11 }, stepSize: 1 }, border: { display: false }, min: 0 }
            }
        }
    });

    new Chart(document.getElementById('weeklyChart'), {
        type: 'line',
        data: {
            labels: weekLabels,
            datasets: [
                {
                    label: 'Builds done',
                    data: weekBuilds,
                    borderColor: '#1B6FC8', backgroundColor: 'rgba(27,111,200,0.08)',
                    borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#1B6FC8',
                    pointBorderColor: '#F4F6FA', pointBorderWidth: 2, tension: 0.35, fill: true
                },
                {
                    label: 'Defects / cancelled',
                    data: weekDefects,
                    borderColor: '#DC2626', backgroundColor: 'rgba(220,38,38,0.06)',
                    borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#DC2626',
                    pointBorderColor: '#F4F6FA', pointBorderWidth: 2, tension: 0.35, fill: true
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#5B7A9D', font: { size: 11 } }, border: { color: '#1B3A6B' } },
                y: { grid: { color: '#E2E8F0' }, ticks: { color: '#5B7A9D', font: { size: 11 }, stepSize: 2 }, border: { display: false }, min: 0 }
            }
        }
    });

    new Chart(document.getElementById('partsDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Ready', 'Sourcing', 'Missing'],
            datasets: [{
                data: [partsReady, partsSourcing, partsMissing],
                backgroundColor: ['#16A34A', '#D97706', '#DC2626'],
                borderColor: '#E2E8F0', borderWidth: 3, hoverOffset: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
    });
})();
