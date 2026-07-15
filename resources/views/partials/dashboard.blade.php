@php
    $total      = count($workOrders);
    $building   = collect($workOrders)->where('status', 'Building')->count();
    $qcCheck    = collect($workOrders)->where('status', 'QC Check')->count();
    $pending    = collect($workOrders)->where('status', 'Pending')->count();
    $finished   = collect($workOrders)->where('status', 'Finished')->count();
    $cancelled  = collect($workOrders)->where('status', 'Cancelled')->count();
    $active     = $building + $qcCheck + $pending;

    $overdue = collect($workOrders)
        ->filter(fn($o) => str_starts_with($o['due'], 'Due') && $o['status'] === 'Cancelled')
        ->count();

    $qcDenom   = $finished + $cancelled;
    $qcRate    = $qcDenom > 0 ? round(($finished / $qcDenom) * 100) : 0;

    $partIssues = [];
    foreach ($workOrders as $wo) {
        foreach ($wo['parts'] as $part) {
            if (in_array($part['status'], ['Sourcing', 'Missing'])) {
                $key = $part['name'];
                if (!isset($partIssues[$key])) {
                    $partIssues[$key] = ['name' => $part['name'], 'status' => $part['status'], 'count' => 0];
                }
                $partIssues[$key]['count']++;
                if ($part['status'] === 'Missing') {
                    $partIssues[$key]['status'] = 'Missing';
                }
            }
        }
    }
    usort($partIssues, fn($a, $b) =>
        ($b['status'] === 'Missing' ? 1 : 0) - ($a['status'] === 'Missing' ? 1 : 0)
        ?: $b['count'] - $a['count']
    );
    $topPartIssues = array_slice(array_values($partIssues), 0, 6);

    $activeOrders = collect($workOrders)
        ->whereIn('status', ['Building', 'QC Check', 'Pending', 'Cancelled'])
        ->take(5)
        ->values();

    $alerts = [];
    $missingParts = collect($workOrders)
        ->flatMap(fn($wo) => collect($wo['parts'])->where('status', 'Missing')->map(fn($p) => $p['name']))
        ->unique()->take(2)->values();
    if ($missingParts->count()) {
        $alerts[] = ['type' => 'danger', 'icon' => 'alert-circle',
            'title' => $missingParts->count() . ' part(s) missing across orders',
            'sub'   => $missingParts->implode(' · ')];
    }
    if ($overdue > 0) {
        $overdueIds = collect($workOrders)
            ->filter(fn($o) => str_starts_with($o['due'], 'Due') && $o['status'] === 'Cancelled')
            ->pluck('id')->implode(' · ');
        $alerts[] = ['type' => 'warning', 'icon' => 'clock',
            'title' => $overdue . ' order(s) cancelled / overdue',
            'sub'   => $overdueIds];
    }
    $qcWarns = collect($workOrders)->where('status', 'QC Check')->count();
    if ($qcWarns > 0) {
        $alerts[] = ['type' => 'warning', 'icon' => 'shield-x',
            'title' => $qcWarns . ' build(s) currently in QC',
            'sub'   => 'Review benchmark results before marking done'];
    }
    $newOrders = collect($workOrders)->where('status', 'Pending')->count();
    if ($newOrders > 0) {
        $alerts[] = ['type' => 'info', 'icon' => 'truck-delivery',
            'title' => $newOrders . ' pending order(s) from e-commerce',
            'sub'   => 'Added to queue · ready to assign'];
    }

    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $weekCounts = [4, 6, 5, 8, 7, 3, $finished];
@endphp

<div class="grid grid-cols-4 gap-3 mb-4">

    {{-- Active Orders --}}
    <div class="bg-nexora-slate-200 rounded-xl px-4 py-3 border border-nexora-corporate/50">
        <p class="text-xs text-nexora-navy-mid mb-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 0 2-2h2a2 2 0 0 0 2 2"/></svg>
            Active Work Orders
        </p>
        <p class="text-3xl font-heading font-medium text-nexora-deep-navy">{{ $active }}</p>
        <p class="text-xs text-nexora-navy-mid mt-1">{{ $building }} building · {{ $qcCheck }} in QC · {{ $pending }} queued</p>
    </div>

    {{-- Completed --}}
    <div class="bg-nexora-slate-200 rounded-xl px-4 py-3 border border-nexora-corporate/50">
        <p class="text-xs text-nexora-navy-mid mb-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            Completed Builds
        </p>
        <p class="text-3xl font-heading font-medium text-nexora-success">{{ $finished }}</p>
        <p class="text-xs text-nexora-navy-mid mt-1">out of {{ $total }} total orders</p>
    </div>

    {{-- Overdue --}}
    <div class="bg-nexora-slate-200 rounded-xl px-4 py-3 border border-nexora-corporate/50">
        <p class="text-xs text-nexora-navy-mid mb-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Cancelled / Overdue
        </p>
        <p class="text-3xl font-heading font-medium {{ $overdue > 0 ? 'text-nexora-danger' : 'text-nexora-deep-navy' }}">{{ $overdue }}</p>
        <p class="text-xs {{ $overdue > 0 ? 'text-nexora-danger' : 'text-nexora-navy-mid' }} mt-1">
            {{ $overdue > 0 ? 'Needs attention' : 'All on track' }}
        </p>
    </div>

    {{-- QC Pass Rate --}}
    <div class="bg-nexora-slate-200 rounded-xl px-4 py-3 border border-nexora-corporate/50">
        <p class="text-xs text-nexora-navy-mid mb-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            QC Pass rate
        </p>
        <p class="text-3xl font-heading font-medium {{ $qcRate >= 80 ? 'text-nexora-success' : 'text-nexora-warning' }}">{{ $qcRate }}%</p>
        <p class="text-xs text-nexora-navy-mid mt-1">{{ $finished }} passed · {{ $cancelled }} failed / cancelled</p>
    </div>
</div>
<div class="grid grid-cols-[1fr_280px] gap-3 mb-4">

    {{-- Active Orders List --}}
    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-3">Active work orders</p>
        <div class="flex flex-col divide-y divide-nexora-corporate/30">
            @foreach($activeOrders as $wo)
                @php
                    $pill = $statusStyles[$wo['status']]['pill'] ?? 'bg-nexora-gray/80 text-nexora-off-white';
                    $isOverdue = str_starts_with($wo['due'], 'Due') && $wo['status'] === 'Cancelled';
                @endphp
                <div class="flex items-center gap-3 p-2.5 hover:bg-nexora-steel-blue transition duration-300">
                    {{-- Icon --}}
                    <div class="w-8 h-8 rounded-lg bg-nexora-slate-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-nexora-corporate" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    </div>
                    {{-- Meta --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-nexora-deep-navy truncate">{{ $wo['name'] }}</p>
                        <p class="text-xs text-nexora-navy-mid font-mono">{{ $wo['id'] }}</p>
                    </div>
                    {{-- Right --}}
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $pill }}">{{ $wo['status'] }}</span>
                        <span class="text-xs {{ $isOverdue ? 'text-nexora-danger' : 'text-nexora-navy-mid' }}">
                            {{ $wo['due'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
        <a href="?page=orders&sub=all"
           class="mt-3 block text-center text-xs text-nexora-corporate hover:text-nexora-corporate transition-colors duration-150">
            View all work orders →
        </a>
    </div>

    {{-- Alerts --}}
    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-3">
            Alerts
            <span class="ml-1 px-1.5 py-0.5 rounded-full bg-nexora-danger/20 text-nexora-danger text-xs">{{ count($alerts) }}</span>
        </p>
        <div class="flex flex-col gap-2">
            @foreach($alerts as $alert)
                @php
                    $alertBg = match($alert['type']) {
                        'danger'  => 'bg-nexora-danger/10 border-nexora-danger/40',
                        'warning' => 'bg-nexora-warning/10 border-nexora-warning/40',
                        'info'    => 'bg-nexora-info/10 border-nexora-info/40',
                        default   => 'bg-nexora-slate-500/20 border-nexora-corporate/30',
                    };
                    $alertIcon = match($alert['type']) {
                        'danger'  => 'text-nexora-danger',
                        'warning' => 'text-nexora-warning',
                        'info'    => 'text-nexora-info',
                        default   => 'text-nexora-navy-mid',
                    };
                @endphp
                <div class="rounded-lg border px-3 py-2 {{ $alertBg }}">
                    <div class="flex items-start gap-2">
                        @if($alert['icon'] === 'alert-circle')
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 {{ $alertIcon }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        @elseif($alert['icon'] === 'clock')
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 {{ $alertIcon }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        @elseif($alert['icon'] === 'shield-x')
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 {{ $alertIcon }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9.5 9.5 5 5M14.5 9.5l-5 5"/></svg>
                        @else
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 {{ $alertIcon }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12H3l9-9 9 9h-2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z"/></svg>
                        @endif
                        <div>
                            <p class="text-xs font-medium text-nexora-deep-navy">{{ $alert['title'] }}</p>
                            <p class="text-xs text-nexora-navy-mid mt-0.5">{{ $alert['sub'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
<div class="grid grid-cols-2 gap-3">

    {{-- Parts with Issues --}}
    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-3">Parts needing attention</p>
        <div class="flex flex-col gap-2">
            @forelse($topPartIssues as $part)
                @php
                    $isMissing  = $part['status'] === 'Missing';
                    $barColor   = $isMissing ? 'bg-nexora-danger' : 'bg-nexora-warning';
                    $textColor  = $isMissing ? 'text-nexora-danger' : 'text-nexora-warning';
                    $barWidth   = $isMissing ? 'w-1/12' : 'w-3/12';
                @endphp
                <div class="flex items-center gap-2">
                    <span class="text-xs text-nexora-navy-mid w-40 truncate flex-shrink-0" title="{{ $part['name'] }}">{{ $part['name'] }}</span>
                    <div class="flex-1 h-1.5 bg-nexora-slate-500/20 rounded-full overflow-hidden">
                        <div class="{{ $barColor }} {{ $barWidth }} h-full rounded-full"></div>
                    </div>
                    <span class="text-xs {{ $textColor }} w-16 text-right flex-shrink-0">{{ $part['status'] }} ×{{ $part['count'] }}</span>
                </div>
            @empty
                <p class="text-xs text-nexora-navy-mid">All parts are accounted for.</p>
            @endforelse
        </div>
        <p class="text-xs text-nexora-navy-mid mt-3">
            Showing parts marked <span class="text-nexora-danger">Missing</span> or <span class="text-nexora-warning">Sourcing</span> across active orders.
        </p>
    </div>

    {{-- Weekly Builds Chart --}}
    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-3">Builds completed this week</p>
        <div class="relative" style="height:140px">
            <canvas id="dashWeekChart" aria-label="Bar chart of builds completed per day this week"></canvas>
        </div>
    </div>
</div>

<script>
    window.dashboardData = {
        days: @json($days),
        weekCounts: @json($weekCounts)
    };
</script>
<script src="{{ asset('js/dashboard-charts.js') }}"></script>
