@php
$selectedIndex = (int) request()->get('order', -1);
@endphp

<div class="flex gap-2 h-[calc(100vh-10rem)]">
    {{-- Left Column: Work Orders --}}
    <div class="w-[32%] flex-shrink-0 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <h2 class="font-heading font-medium text-xl text-nexora-navy-mid whitespace-nowrap">
                Work Orders
            </h2>
            <button onclick="openAddWorkerModal()" class="px-3 py-1.5 rounded bg-nexora-corporate text-white text-sm hover:bg-nexora-navy-mid transition-colors">
                + Add Worker
            </button>
        </div>

        <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-nexora-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="searchWO" placeholder="Search orders..." oninput="filterOrders()"
                   class="w-full pl-8 pr-3 py-1.5 rounded-md bg-nexora-steel-blue/50 text-nexora-deep-navy text-xs placeholder-nexora-navy/50 border border-nexora-corporate focus:outline-none focus:border-nexora-deep-navy">
        </div>

        <div class="flex gap-2 my-2 flex-wrap">
            <button data-filter="all" onclick="filterOrders('all')" class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate bg-nexora-corporate text-white transition-colors">All</button>
            <button data-filter="Building" onclick="filterOrders('Building')" class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate text-nexora-deep-navy hover:bg-nexora-corporate hover:text-white transition-colors">Building</button>
            <button data-filter="Pending" onclick="filterOrders('Pending')" class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate text-nexora-deep-navy hover:bg-nexora-corporate hover:text-white transition-colors">Pending</button>
            <button data-filter="Finished" onclick="filterOrders('Finished')" class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate text-nexora-deep-navy hover:bg-nexora-corporate hover:text-white transition-colors">Finished</button>
            <button data-filter="QC Check" onclick="filterOrders('QC Check')" class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate text-nexora-deep-navy hover:bg-nexora-corporate hover:text-white transition-colors">QC Check</button>
        </div>

        <div class="flex-1 rounded-lg bg-nexora-slate-200 border border-nexora-corporate/50 px-1 py-3 overflow-y-auto">
            @foreach($workOrders as $i => $order)
                @php
                    $style = $statusStyles[$order['status']] ?? ['pill' => 'bg-gray-400 text-white'];
                    $isActive = $i === $selectedIndex;
                @endphp
                <div id="card-{{ $i }}" data-index="{{ $i }}" data-status="{{ $order['status'] }}" onclick="toggleOrder({{ $i }})"
                     class="block px-3 py-2.5 mb-1.5 cursor-pointer transition-all duration-150 hover:shadow-md hover:-translate-y-[2px] hover:bg-nexora-steel-blue/50 {{ $isActive ? 'bg-nexora-steel-blue/80' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] text-nexora-navy mb-0.5">{{ $order['id'] }}</p>
                            <p class="text-sm font-semibold text-nexora-deep-navy truncate">{{ $order['name'] }}</p>
                            <p class="text-[10px] text-nexora-navy mt-0.5 truncate">{{ $order['specs'] }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $style['pill'] }}">{{ $order['status'] }}</span>
                            <span class="text-[10px] text-nexora-navy-mid">{{ $order['due'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Right Panel --}}
    <div id="right-panel" class="w-[65%] relative flex-shrink-0 bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl overflow-hidden text-nexora-deep-navy">
        <div id="worker-management" class="p-5 h-full overflow-y-auto">
            <h3 class="text-xl font-bold mb-4">Worker Management</h3>
            <p class="text-sm text-nexora-slate-500 mb-4">Click any worker to view, edit or delete</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($workers as $worker)
                    <div class="worker-item p-3 rounded-lg bg-white border border-nexora-corporate/30 hover:shadow-md hover:border-nexora-corporate/60 cursor-pointer transition-all" onclick="openEditWorkerModal({{ e(json_encode($worker)) }})">
                        <p class="text-sm font-semibold">{{ $worker['name'] }}</p>
                        <p class="text-xs text-nexora-slate-500">{{ $worker['role'] }}</p>
                        @if(!empty($worker['notes']))
                            <p class="text-xs text-nexora-slate-400 mt-1 line-clamp-2">{{ $worker['notes'] }}</p>
                        @endif
                    </div>
                @endforeach
                @if(empty($workers))
                    <p class="text-sm text-nexora-slate-500 italic col-span-2 text-center py-8">No workers added yet</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modals & Scripts --}}
<div id="edit-worker-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[999] hidden" onclick="closeEditWorkerModal()">
    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl relative" onclick="event.stopPropagation()">
        <h3 class="text-lg font-bold text-nexora-deep-navy mb-4">Edit Worker</h3>
        <form id="edit-worker-form" onsubmit="submitEditWorker(event)">
            <input type="hidden" id="edit-w-id">
            <div class="mb-3">
                <label class="text-xs text-nexora-slate-600 mb-1 block">Full Name</label>
                <input type="text" id="edit-w-name" class="w-full border border-nexora-corporate/50 rounded p-2 text-sm text-gray-800 bg-white focus:outline-none focus:border-nexora-deep-navy" required>
            </div>
            <div class="mb-3">
                <label class="text-xs text-nexora-slate-600 mb-1 block">Role / Specialty</label>
                <input type="text" id="edit-w-role" class="w-full border border-nexora-corporate/50 rounded p-2 text-sm text-gray-800 bg-white focus:outline-none focus:border-nexora-deep-navy" required>
            </div>
            <div class="mb-5">
                <label class="text-xs text-nexora-slate-600 mb-1 block">Notes / Bio</label>
                <textarea id="edit-w-notes" rows="3" class="w-full border border-nexora-corporate/50 rounded p-2 text-sm text-gray-800 bg-white focus:outline-none focus:border-nexora-deep-navy"></textarea>
            </div>
            <div class="flex justify-between items-center gap-3">
                <button type="button" onclick="confirmDeleteWorker()" class="px-3 py-2 rounded bg-red-500 text-white text-sm hover:bg-red-600 transition-colors">Delete Worker</button>
                <div class="flex gap-2">
                    <button type="button" onclick="closeEditWorkerModal()" class="px-3 py-2 rounded bg-gray-200 text-gray-700 text-sm hover:bg-gray-300 transition-colors">Cancel</button>
                    <button type="submit" class="px-3 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700 transition-colors">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="add-worker-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[998] hidden" onclick="closeAddWorkerModal()">
    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl relative" onclick="event.stopPropagation()">
        <h3 class="text-lg font-bold text-nexora-deep-navy mb-4">Add New Worker</h3>
        <form id="add-worker-form" onsubmit="submitAddWorker(event)">
            <div class="mb-3">
                <label class="text-xs text-nexora-slate-600 mb-1 block">Full Name</label>
                <input type="text" id="add-w-name" class="w-full border border-nexora-corporate/50 rounded p-2 text-sm text-gray-800 bg-white focus:outline-none focus:border-nexora-deep-navy" required>
            </div>
            <div class="mb-3">
                <label class="text-xs text-nexora-slate-600 mb-1 block">Role / Specialty</label>
                <input type="text" id="add-w-role" class="w-full border border-nexora-corporate/50 rounded p-2 text-sm text-gray-800 bg-white focus:outline-none focus:border-nexora-deep-navy" required>
            </div>
            <div class="mb-5">
                <label class="text-xs text-nexora-slate-600 mb-1 block">Notes / Bio</label>
                <textarea id="add-w-notes" rows="3" class="w-full border border-nexora-corporate/50 rounded p-2 text-sm text-gray-800 bg-white focus:outline-none focus:border-nexora-deep-navy"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeAddWorkerModal()" class="px-3 py-2 rounded bg-gray-200 text-gray-700 text-sm hover:bg-gray-300 transition-colors">Cancel</button>
                <button type="submit" class="px-3 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700 transition-colors">Add Worker</button>
            </div>
        </form>
    </div>
</div>

<div id="success-notif" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[999] hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl text-center">
        <p id="success-text" class="text-base text-gray-800"></p>
        <button onclick="closeSuccessNotif()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">OK</button>
    </div>
</div>

<script>
let draggedWorker = null;
const CURRENT_SELECTED = {{ $selectedIndex ?? -1 }};

function toggleOrder(index) {
    const detailEl = document.getElementById(`detail-${index}`);
    if (index === CURRENT_SELECTED) {
        closeAssignmentPanel();
    } else {
        document.querySelectorAll('[id^="detail-"]').forEach(el => el.classList.add('translate-x-full'));
        if (detailEl) detailEl.classList.remove('translate-x-full');
        history.replaceState({}, '', `?page=orders&sub=assignment&order=${index}`);
        document.querySelectorAll('[id^="card-"]').forEach((card, i) => {
            card.classList.remove('bg-nexora-steel-blue/80');
            if (i === index) card.classList.add('bg-nexora-steel-blue/80');
        });
    }
}

function closeAssignmentPanel() {
    document.querySelectorAll('[id^="detail-"]').forEach(el => el.classList.add('translate-x-full'));
    history.replaceState({}, '', `?page=orders&sub=assignment`);
    document.querySelectorAll('[id^="card-"]').forEach(card => card.classList.remove('bg-nexora-steel-blue/80'));
}

function openEditWorkerModal(worker) {
    document.getElementById('edit-w-id').value = worker.id;
    document.getElementById('edit-w-name').value = worker.name;
    document.getElementById('edit-w-role').value = worker.role;
    document.getElementById('edit-w-notes').value = worker.notes || '';
    document.getElementById('edit-worker-modal').classList.remove('hidden');
}

function closeEditWorkerModal() {
    document.getElementById('edit-worker-modal').classList.add('hidden');
}

function submitEditWorker(e) {
    e.preventDefault();
    const updated = {
        id: document.getElementById('edit-w-id').value,
        name: document.getElementById('edit-w-name').value.trim(),
        role: document.getElementById('edit-w-role').value.trim(),
        notes: document.getElementById('edit-w-notes').value.trim()
    };
    fetch(`/manufacturing/update-worker`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(updated)
    })
    .then(res => res.json())
    .then(() => {
        closeEditWorkerModal();
        showSuccess('Worker updated successfully');
        setTimeout(() => location.reload(), 800);
    });
}

function confirmDeleteWorker() {
    const id = document.getElementById('edit-w-id').value;
    const name = document.getElementById('edit-w-name').value;
    if (!confirm(`Delete "${name}"?`)) return;
    fetch(`/manufacturing/delete-worker`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(() => {
        closeEditWorkerModal();
        showSuccess('Worker deleted successfully');
        setTimeout(() => location.reload(), 800);
    });
}

function openAddWorkerModal() {
    document.getElementById('add-worker-form').reset();
    document.getElementById('add-worker-modal').classList.remove('hidden');
}

function closeAddWorkerModal() {
    document.getElementById('add-worker-modal').classList.add('hidden');
}

function submitAddWorker(e) {
    e.preventDefault();
    const newWorker = {
        id: Date.now(),
        name: document.getElementById('add-w-name').value.trim(),
        role: document.getElementById('add-w-role').value.trim(),
        notes: document.getElementById('add-w-notes').value.trim()
    };
    fetch(`/manufacturing/add-worker`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(newWorker)
    })
    .then(res => res.json())
    .then(() => {
        closeAddWorkerModal();
        showSuccess('Worker added successfully');
        setTimeout(() => location.reload(), 800);
    });
}

function allowDrop(e) { e.preventDefault(); e.dataTransfer.dropEffect = "move"; }
function dragWorker(e, worker) { draggedWorker = worker; e.dataTransfer.setData('application/json', JSON.stringify(worker)); }
function dropAssign(e, orderIndex) { e.preventDefault(); if (!draggedWorker) return; }

function filterOrders(filter = 'all') {
    const search = document.getElementById('searchWO').value.toLowerCase();
    const cards = document.querySelectorAll('[id^="card-"]');
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-nexora-corporate', 'text-white');
        btn.classList.add('text-nexora-deep-navy');
    });
    if (event?.target) {
        event.target.classList.add('bg-nexora-corporate', 'text-white');
        event.target.classList.remove('text-nexora-deep-navy');
    }
    cards.forEach(card => {
        const status = card.dataset.status;
        const text = card.textContent.toLowerCase();
        const matchesSearch = text.includes(search);
        const matchesFilter = filter === 'all' || status === filter;
        card.style.display = (matchesSearch && matchesFilter) ? 'block' : 'none';
    });
}

function showSuccess(msg) {
    document.getElementById('success-text').textContent = msg;
    document.getElementById('success-notif').classList.remove('hidden');
}

function closeSuccessNotif() {
    document.getElementById('success-notif').classList.add('hidden');
}
</script>
