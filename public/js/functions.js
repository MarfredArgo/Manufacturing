// ── Sidebar Page Switching ─────────────────────────────────────────────────
function switchPage(page) {
    document.querySelectorAll('.page-content').forEach(el => {
        el.classList.add('hidden');
    });

    document.getElementById('page-' + page).classList.remove('hidden');

    document.querySelectorAll('.nav-link').forEach(el => {
        el.classList.remove('text-nexora-off-white', 'underline', 'decoration-2', 'underline-offset-2');
        el.classList.add('text-nexora-corporate/80');
    });

    document.querySelector(`[data-page="${page}"]`).classList.add(
        'text-nexora-off-white', 'underline', 'decoration-2', 'underline-offset-2'
    );
    document.querySelector(`[data-page="${page}"]`).classList.remove('text-nexora-corporate/80');
}

// ── Status: Show Order Detail ──────────────────────────────────────────────
function showOrder(index) {
    document.querySelectorAll('[id^="detail-"]').forEach(el => {
        el.classList.add('hidden');
    });

    document.getElementById('detail-' + index).classList.remove('hidden');

    document.querySelectorAll('[id^="card-"]').forEach(el => {
        el.classList.remove('bg-nexora-steel-blue/80');
        el.classList.add('hover:bg-nexora-steel-blue/50', 'hover:-translate-y-[2px]', 'hover:shadow-md');
    });

    const activeCard = document.getElementById('card-' + index);
    activeCard.classList.add('bg-nexora-steel-blue/80');
    activeCard.classList.remove('hover:bg-nexora-steel-blue/50', 'hover:-translate-y-[2px]', 'hover:shadow-md');
}

// ── Filter ─────────────────────────────────────────────────────────────────
let currentFilter = 'all';
function filterOrders(status) {
    currentFilter = status;
    const search = document.getElementById('search-input').value.toLowerCase();

    document.querySelectorAll('[id^="card-"]').forEach(card => {
        const matchesStatus = status === 'all' || card.dataset.status === status;
        const matchesSearch = card.dataset.name.toLowerCase().includes(search);

        if (matchesStatus && matchesSearch) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-nexora-corporate', 'text-white');
        btn.classList.add('text-nexora-deep-navy');
    });

    document.querySelector(`[data-filter="${status}"]`).classList.add('bg-nexora-corporate', 'text-white');
    document.querySelector(`[data-filter="${status}"]`).classList.remove('text-nexora-deep-navy');

    reanimateRows();
}

// ── Table Row Animation ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.row-animate').forEach(row => {
        row.addEventListener('animationend', () => {
            row.classList.add('done');
        });
    });

    reanimateRows();
});

function reanimateRows() {
    const visibleRows = document.querySelectorAll('.row-animate:not(.hidden)');

    visibleRows.forEach(row => row.classList.remove('animate', 'done'));

    setTimeout(() => {
        visibleRows.forEach((row, i) => {
            setTimeout(() => row.classList.add('animate'), i * 20);
        });
    }, 20);
}

// ── Dashboard & Reports Charts ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    if (document.getElementById('dashWeekChart') && window.dashboardData) {
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
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.parsed.y + ' builds' } } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#869FB1', font: { size: 11 } }, border: { color: '#1B3A6B' } },
                    y: { grid: { color: '#1B3A6B' }, ticks: { color: '#869FB1', font: { size: 11 }, stepSize: 2 }, border: { display: false }, min: 0 }
                }
            }
        });
    }

    if (document.getElementById('statusChart') && window.reportsData) {
        const { statusLabels, statusCounts, statusColors, weekLabels, weekBuilds, weekDefects, partsReady, partsSourcing, partsMissing } = window.reportsData;

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
                    x: { grid: { display: false }, ticks: { color: '#869FB1', font: { size: 11 } }, border: { color: '#1B3A6B' } },
                    y: { grid: { color: '#1B3A6B' }, ticks: { color: '#869FB1', font: { size: 11 }, stepSize: 1 }, border: { display: false }, min: 0 }
                }
            }
        });

        new Chart(document.getElementById('weeklyChart'), {
            type: 'line',
            data: {
                labels: weekLabels,
                datasets: [
                    { label: 'Builds done', data: weekBuilds, borderColor: '#1B6FC8', backgroundColor: 'rgba(27,111,200,0.08)', borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#1B6FC8', pointBorderColor: '#0B1E3D', pointBorderWidth: 2, tension: 0.35, fill: true },
                    { label: 'Defects / cancelled', data: weekDefects, borderColor: '#DC2626', backgroundColor: 'rgba(220,38,38,0.06)', borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#DC2626', pointBorderColor: '#0B1E3D', pointBorderWidth: 2, tension: 0.35, fill: true }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#869FB1', font: { size: 11 } }, border: { color: '#1B3A6B' } },
                    y: { grid: { color: '#1B3A6B' }, ticks: { color: '#869FB1', font: { size: 11 }, stepSize: 2 }, border: { display: false }, min: 0 }
                }
            }
        });

        new Chart(document.getElementById('partsDonut'), {
            type: 'doughnut',
            data: {
                labels: ['Ready', 'Sourcing', 'Missing'],
                datasets: [{ data: [partsReady, partsSourcing, partsMissing], backgroundColor: ['#16A34A', '#D97706', '#DC2626'], borderColor: '#132B52', borderWidth: 3, hoverOffset: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
        });
    }
    
    if (document.getElementById('qcVerdictDonut') && window.qcAnalyticsData) {
        const { verdictLabels, verdictCounts, verdictColors } = window.qcAnalyticsData;
        new Chart(document.getElementById('qcVerdictDonut'), {
            type: 'doughnut',
            data: {
                labels: verdictLabels,
                datasets: [{ data: verdictCounts, backgroundColor: verdictColors, borderColor: '#E2E8F0', borderWidth: 3, hoverOffset: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
        });
    }
});

// ── Generic Modal Helpers ──────────────────────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.body.style.overflow = '';
}

function handleBackdropClick(event, id) {
    if (event.target === event.currentTarget) closeModal(id);
}

// Close any open modal on Escape
document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.modal-backdrop:not(.hidden)').forEach(el => {
        closeModal(el.id);
    });
});

// ── Status Edit Modal ──────────────────────────────────────────────────────
let editingOrderIndex = null;
let pendingChanges    = {};
let pendingQC         = false;

function openEditModal(i) {
    editingOrderIndex = i;
    pendingChanges    = {};
    pendingQC         = false;

    const order = workOrdersData[i];

    document.getElementById('modal-order-id').textContent   = order.id + ' • ' + order.source;
    document.getElementById('modal-order-name').textContent = order.name;

    const statusEl = document.getElementById('modal-order-status');
    statusEl.textContent = order.status;
    statusEl.className   = 'px-2.5 py-1 rounded-full text-xs font-bold ' + getStatusPill(order.status);

    document.getElementById('section-order-status').classList.toggle('hidden', order.status !== 'Finished');

    renderPartsList(order.parts);

    document.getElementById('modal-save-msg').classList.add('hidden');

    openModal('edit-backdrop');
}

function renderPartsList(parts) {
    const list = document.getElementById('modal-parts-list');
    list.innerHTML = '';

    parts.forEach((part, idx) => {
        const isSourcing = part.status === 'Sourcing';
        const isMissing  = part.status === 'Missing';
        const isReady    = part.status === 'Ready';

        const dotColor  = isReady    ? 'bg-green-500'
                        : isSourcing ? 'bg-yellow-400'
                        : 'bg-red-500';

        const textColor = isReady    ? 'text-green-600'
                        : isSourcing ? 'text-yellow-600'
                        : 'text-red-500';

        const toggleBtn = isSourcing
            ? `<button onclick="markReady(${idx})"
                       id="toggle-${idx}"
                       class="flex-shrink-0 ml-3 px-3 py-1 rounded-full text-[10px] font-semibold
                              border border-green-500 text-green-600
                              hover:bg-green-500 hover:text-white transition-colors">
                   Mark Ready
               </button>`
            : isMissing
            ? `<span class="flex-shrink-0 ml-3 px-3 py-1 rounded-full text-[10px] font-semibold
                           bg-red-100 text-red-400 border border-red-200 cursor-not-allowed"
                    title="Out of stock — cannot change">
                   Out of Stock
               </span>`
            : `<span class="flex-shrink-0 ml-3 px-2 py-1 rounded-full text-[10px] font-semibold
                           bg-green-100 text-green-600">
                   ✓ Ready
               </span>`;

        list.innerHTML += `
            <div id="part-row-${idx}"
                 class="flex items-center justify-between px-3 py-2.5 rounded-lg
                        bg-nexora-slate-200 border border-nexora-corporate/20">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span id="dot-${idx}" class="w-2 h-2 rounded-full flex-shrink-0 ${dotColor}"></span>
                    <span class="text-sm text-nexora-deep-navy font-medium">${part.category} →</span>
                    <span class="text-xs text-nexora-navy-mid truncate">${part.name}</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span id="status-label-${idx}" class="text-xs font-medium ${textColor}">${part.status}</span>
                    ${toggleBtn}
                </div>
            </div>`;
    });
}

function markReady(partIdx) {
    pendingChanges[partIdx] = 'Ready';

    document.getElementById(`dot-${partIdx}`).className           = 'w-2 h-2 rounded-full flex-shrink-0 bg-green-500';
    document.getElementById(`status-label-${partIdx}`).className  = 'text-xs font-medium text-green-600';
    document.getElementById(`status-label-${partIdx}`).textContent = 'Ready';

    const btn = document.getElementById(`toggle-${partIdx}`);
    if (btn) {
        btn.outerHTML = `<span class="flex-shrink-0 ml-3 px-2 py-1 rounded-full text-[10px] font-semibold
                                     bg-green-100 text-green-600">✓ Ready</span>`;
    }
}

function sendToQC() {
    pendingQC = true;
    const statusEl = document.getElementById('modal-order-status');
    statusEl.textContent = 'QC Check';
    statusEl.className   = 'px-2.5 py-1 rounded-full text-xs font-bold bg-blue-400 text-blue-900';
    document.getElementById('section-order-status').classList.add('hidden');
}

async function saveChanges() {
    if (editingOrderIndex === null) return;
    if (Object.keys(pendingChanges).length === 0 && !pendingQC) {
        closeModal('edit-backdrop');
        return;
    }

    const order = workOrdersData[editingOrderIndex];

    const allReady = order.parts.every((part, idx) => {
        const finalStatus = pendingChanges[idx] ?? part.status;
        return finalStatus === 'Ready';
    });

    const autoFinish = allReady && order.status === 'Building';

    const payload = {
        orderIndex:  editingOrderIndex,
        partChanges: pendingChanges,
        sendToQC:    pendingQC,
        _token:      document.querySelector('meta[name="csrf-token"]').content,
    };

    try {
        const res  = await fetch('/manufacturing/update-order', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token },
            body:    JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('modal-save-msg').classList.remove('hidden');
            setTimeout(() => window.location.reload(), 800);
        } else {
            alert('Save failed: ' + (data.message ?? 'Unknown error'));
        }
    } catch (err) {
        alert('Network error — could not save changes.');
        console.error(err);
    }
}

function getStatusPill(status) {
    const map = {
        'Building': 'bg-yellow-400 text-yellow-900',
        'Pending':  'bg-red-500 text-white',
        'Finished': 'bg-green-500 text-white',
        'QC Check': 'bg-blue-400 text-blue-900',
        'Cancelled':'bg-gray-400 text-gray-900',
    };
    return map[status] ?? 'bg-gray-300 text-gray-800';
}

// ── Benchmark Edit Modal ───────────────────────────────────────────────────
let bmRows = {};

function openBenchmarkModal() {
    bmRows = {};
    renderBenchmarkChecks();
    updateBenchmarkCounts();
    document.getElementById('bm-save-msg').classList.add('hidden');
    openModal('benchmark-backdrop');  // ← uses generic openModal
}

function renderBenchmarkChecks() {
    const list    = document.getElementById('bm-check-list');
    const checks  = benchmarkData.checks;
    const results = benchmarkData.results;

    list.innerHTML = '';
    let lastCat    = '';

    checks.forEach((check, idx) => {
        const existing = results.find(r => r.checkId === check.id) ?? {};

        bmRows[check.id] = {
            value:   existing.value   ?? null,
            verdict: existing.verdict ?? '',
            note:    existing.note    ?? '',
        };

        if (check.category !== lastCat) {
            lastCat = check.category;
            list.innerHTML += `
                <div class="px-2 py-1 mt-2 first:mt-0">
                    <p class="text-[10px] font-semibold text-nexora-corporate uppercase tracking-wider">
                        ${check.category}
                    </p>
                </div>`;
        }

        const num        = String(idx + 1).padStart(2, '0');
        const curVal     = bmRows[check.id].value   ?? '';
        const curNote    = bmRows[check.id].note    ?? '';
        const curVerdict = bmRows[check.id].verdict ?? '';
        const isPassFail = check.unit === 'pass';
        const needsFormat = ['pts','MB/s','MT/s'].includes(check.unit);
        const targetDisp  = (needsFormat ? Number(check.target).toLocaleString() : check.target)
                          + (check.unit !== 'pass' ? ' ' + check.unit : '');

        list.innerHTML += `
            <div class="bg-nexora-slate-200 border border-nexora-corporate/20 rounded-xl px-4 py-3 flex flex-col gap-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-2 min-w-0">
                        <span class="text-[10px] font-mono text-nexora-navy-mid flex-shrink-0 mt-0.5">${num}</span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-nexora-deep-navy">${check.name}</p>
                            <p class="text-[10px] text-nexora-navy-mid mt-0.5">
                                ${check.tool} &bull; Target: ${check.operator} ${targetDisp}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        ${['Pass','Warn','Fail'].map(v => `
                            <button id="vbtn-${check.id}-${v}"
                                    onclick="setBenchmarkVerdict('${check.id}', '${v}')"
                                    class="verdict-btn px-2.5 py-1 rounded-full text-[10px] font-semibold border transition-colors
                                           ${curVerdict === v ? activePillClass(v) : inactivePillClass(v)}">
                                ${v}
                            </button>`).join('')}
                    </div>
                </div>
                <div class="flex gap-2">
                    ${!isPassFail ? `
                    <div class="flex items-center gap-1.5 bg-nexora-off-white border border-nexora-corporate/30
                                rounded-lg px-3 py-1.5 w-36 flex-shrink-0">
                        <input type="number"
                               id="val-${check.id}"
                               value="${curVal}"
                               placeholder="Result"
                               oninput="onBenchmarkValueInput('${check.id}', ${check.target}, '${check.operator}', '${check.unit}')"
                               class="w-full bg-transparent text-xs text-nexora-deep-navy placeholder-nexora-navy-mid/50
                                      focus:outline-none [appearance:textfield]">
                        <span class="text-[10px] text-nexora-navy-mid flex-shrink-0">${check.unit}</span>
                    </div>` : ''}
                    <input type="text"
                           id="note-${check.id}"
                           value="${curNote}"
                           placeholder="Note / observation (optional)"
                           oninput="bmRows['${check.id}'].note = this.value"
                           class="flex-1 bg-nexora-off-white border border-nexora-corporate/30 rounded-lg
                                  px-3 py-1.5 text-xs text-nexora-deep-navy placeholder-nexora-navy-mid/50
                                  focus:outline-none focus:border-nexora-corporate">
                </div>
            </div>`;
    });
}

function activePillClass(v) {
    return v === 'Pass' ? 'bg-nexora-success text-white border-nexora-success'
         : v === 'Warn' ? 'bg-nexora-warning text-white border-nexora-warning'
         :                'bg-nexora-danger text-white border-nexora-danger';
}

function inactivePillClass(v) {
    return v === 'Pass' ? 'border-nexora-success/40 text-nexora-success hover:bg-nexora-success/10'
         : v === 'Warn' ? 'border-nexora-warning/40 text-nexora-warning hover:bg-nexora-warning/10'
         :                'border-nexora-danger/40 text-nexora-danger hover:bg-nexora-danger/10';
}

function setBenchmarkVerdict(checkId, verdict) {
    bmRows[checkId].verdict = verdict;

    ['Pass','Warn','Fail'].forEach(v => {
        const btn = document.getElementById(`vbtn-${checkId}-${v}`);
        if (!btn) return;
        btn.className = `verdict-btn px-2.5 py-1 rounded-full text-[10px] font-semibold border transition-colors
                         ${verdict === v ? activePillClass(v) : inactivePillClass(v)}`;
    });

    updateBenchmarkCounts();
}

function onBenchmarkValueInput(checkId, target, operator, unit) {
    const input = document.getElementById(`val-${checkId}`);
    const val   = parseFloat(input.value);

    bmRows[checkId].value = isNaN(val) ? null : val;

    if (!isNaN(val)) {
        let verdict = '';
        if (operator === '>=')
            verdict = val >= target ? 'Pass' : (val >= target * 0.9 ? 'Warn' : 'Fail');
        else if (operator === '<=')
            verdict = val <= target ? 'Pass' : (val <= target * 1.1 ? 'Warn' : 'Fail');
        else
            verdict = val == target ? 'Pass' : 'Fail';

        setBenchmarkVerdict(checkId, verdict);
    }
}

function updateBenchmarkCounts() {
    let pass = 0, warn = 0, fail = 0;
    Object.values(bmRows).forEach(r => {
        if (r.verdict === 'Pass') pass++;
        else if (r.verdict === 'Warn') warn++;
        else if (r.verdict === 'Fail') fail++;
    });
    document.getElementById('bm-count-pass').textContent = pass + ' Pass';
    document.getElementById('bm-count-warn').textContent = warn + ' Warn';
    document.getElementById('bm-count-fail').textContent = fail + ' Fail';
}

async function saveBenchmarkResults() {
    const results = Object.entries(bmRows).map(([checkId, data]) => ({
        checkId,
        value:   data.value,
        verdict: data.verdict,
        note:    data.note,
    }));

    const payload = {
        woId:    benchmarkData.woId,
        results,
        _token:  document.querySelector('meta[name="csrf-token"]').content,
    };

    try {
        const res  = await fetch('/manufacturing/update-qc', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token },
            body:    JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('bm-save-msg').classList.remove('hidden');
            setTimeout(() => window.location.reload(), 800);
        } else {
            alert('Save failed: ' + (data.message ?? 'Unknown error'));
        }
    } catch (err) {
        alert('Network error — could not save results.');
        console.error(err);
    }
}
