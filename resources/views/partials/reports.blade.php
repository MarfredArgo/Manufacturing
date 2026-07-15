@php
    $total     = count($workOrders);
    $building  = collect($workOrders)->where('status', 'Building')->count();
    $qcCheck   = collect($workOrders)->where('status', 'QC Check')->count();
    $pending   = collect($workOrders)->where('status', 'Pending')->count();
    $finished  = collect($workOrders)->where('status', 'Finished')->count();
    $cancelled = collect($workOrders)->where('status', 'Cancelled')->count();

    $qcDenom  = $finished + $cancelled;
    $qcRate   = $qcDenom > 0 ? round(($finished / $qcDenom) * 100) : 0;

    $defectCount = collect($workOrders)
        ->flatMap(fn($wo) => $wo['parts'])
        ->where('status', 'Missing')
        ->count();

    $avgParts = $total > 0 ? round(collect($workOrders)->sum(fn($wo) => count($wo['parts'])) / $total, 1) : 0;

    $statusLabels = ['Building', 'QC Check', 'Pending', 'Finished', 'Cancelled'];
    $statusCounts = array_map(fn($s) => collect($workOrders)->where('status', $s)->count(), $statusLabels);
    $statusColors = ['#D97706', '#0EA5E9', '#DC2626', '#16A34A', '#9D9D9D'];

    $assignees = collect($workOrders)
        ->groupBy('assigned')
        ->map(fn($group, $name) => ['name' => $name, 'count' => $group->count()])
        ->values()
        ->sortByDesc('count')
        ->values();

    $allParts       = collect($workOrders)->flatMap(fn($wo) => $wo['parts']);
    $partsReady     = $allParts->where('status', 'Ready')->count();
    $partsSourcing  = $allParts->where('status', 'Sourcing')->count();
    $partsMissing   = $allParts->where('status', 'Missing')->count();
    $partsTotal     = $allParts->count();

    $recentOrders = collect($workOrders)
        ->whereIn('status', ['Finished'])
        ->take(5)
        ->values();

    $weekLabels  = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $weekBuilds  = [4, 6, 5, 8, 7, 3, $finished];
    $weekDefects = [1, 0, 2, 1, 0, 1, $cancelled];
@endphp
<div class="flex flex-col h-full">
<h1 class="flex-shrink-0 font-heading font-medium text-2xl text-nexora-deep-navy mb-4">Reports & Analytics</h1>
<div class="flex-1 min-h-0 flex flex-col">
<div class="grid grid-cols-4 gap-3 mb-4 flex-shrink-0">
    <div class="bg-nexora-slate-200 rounded-xl px-4 py-3 border border-nexora-corporate/50">
        <p class="text-xs text-nexora-navy-mid mb-1">Total work orders</p>
        <p class="text-3xl font-heading font-medium text-nexora-deep-navy">{{ $total }}</p>
        <p class="text-xs text-nexora-navy-mid mt-1">across all statuses</p>
    </div>
    <div class="bg-nexora-slate-200 rounded-xl px-4 py-3 border border-nexora-corporate/50">
        <p class="text-xs text-nexora-navy-mid mb-1">QC pass rate</p>
        <p class="text-3xl font-heading font-medium {{ $qcRate >= 80 ? 'text-nexora-success' : 'text-nexora-warning' }}">{{ $qcRate }}%</p>
        <p class="text-xs text-nexora-navy-mid mt-1">{{ $finished }} finished · {{ $cancelled }} cancelled</p>
    </div>
    <div class="bg-nexora-slate-200 rounded-xl px-4 py-3 border border-nexora-corporate/50">
        <p class="text-xs text-nexora-navy-mid mb-1">Parts with issues</p>
        <p class="text-3xl font-heading font-medium {{ $defectCount > 0 ? 'text-nexora-danger' : 'text-nexora-success' }}">{{ $defectCount }}</p>
        <p class="text-xs text-nexora-navy-mid mt-1">{{ $partsSourcing }} sourcing · {{ $partsMissing }} missing</p>
    </div>
    <div class="bg-nexora-slate-200 rounded-xl px-4 py-3 border border-nexora-corporate/50">
        <p class="text-xs text-nexora-navy-mid mb-1">Avg parts per build</p>
        <p class="text-3xl font-heading font-medium text-nexora-deep-navy">{{ $avgParts }}</p>
        <p class="text-xs text-nexora-navy-mid mt-1">{{ $partsTotal }} parts tracked total</p>
    </div>
</div>

<div class="flex gap-3 mb-4 flex-shrink-0">

    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4 flex-1">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-1">Work orders by status</p>
        <p class="text-xs text-nexora-navy-mid mb-3">All orders · current snapshot</p>
        {{-- Custom legend --}}
        <div class="flex flex-wrap gap-3 mb-3">
            @foreach($statusLabels as $i => $label)
                <span class="flex items-center gap-1.5 text-xs text-nexora-navy-mid">
                    <span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:{{ $statusColors[$i] }}"></span>
                    {{ $label }}
                </span>
            @endforeach
        </div>
        <div class="relative" style="height:160px">
            <canvas id="statusChart" aria-label="Bar chart showing work order counts by status"></canvas>
        </div>
    </div>

    {{-- Weekly Output --}}
    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4 flex-1">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-1">Weekly builds vs defects</p>
        <p class="text-xs text-nexora-navy-mid mb-3">Completed builds and cancelled/defect orders per day</p>
        <div class="flex flex-wrap gap-3 mb-3">
            <span class="flex items-center gap-1.5 text-xs text-nexora-navy-mid"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:#1B6FC8"></span>Builds done</span>
            <span class="flex items-center gap-1.5 text-xs text-nexora-navy-mid"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background:#DC2626"></span>Defects / cancelled</span>
        </div>
        <div class="relative" style="height:160px">
            <canvas id="weeklyChart" aria-label="Line chart of weekly builds and defects"></canvas>
        </div>
    </div>

    {{-- Parts Donut --}}
    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4 pt-1 w-[250px] flex flex-col items-center justify-center">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-3 self-start">Parts status</p>
        <div class="relative" style="height:130px;width:130px">
            <canvas id="partsDonut" aria-label="Donut chart showing parts by status: Ready, Sourcing, Missing"></canvas>
        </div>
        <div class="flex flex-col gap-1 mt-3 self-start w-full">
            <span class="flex items-center justify-between text-xs text-nexora-navy-mid">
                <span class="flex items-center gap-1.5"><span class="inline-block w-2 h-2 rounded-full bg-nexora-success"></span>Ready</span>
                <span class="text-nexora-deep-navy">{{ $partsReady }}</span>
            </span>
            <span class="flex items-center justify-between text-xs text-nexora-navy-mid">
                <span class="flex items-center gap-1.5"><span class="inline-block w-2 h-2 rounded-full bg-nexora-warning"></span>Sourcing</span>
                <span class="text-nexora-deep-navy">{{ $partsSourcing }}</span>
            </span>
            <span class="flex items-center justify-between text-xs text-nexora-navy-mid">
                <span class="flex items-center gap-1.5"><span class="inline-block w-2 h-2 rounded-full bg-nexora-danger"></span>Missing</span>
                <span class="text-nexora-deep-navy">{{ $partsMissing }}</span>
            </span>
        </div>
    </div>

</div>

<div class="flex gap-3 mb-4 flex-1 min-h-0">

    {{-- Work Order Summary Table --}}
    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4 flex-1 flex flex-col min-h-0">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-3 flex-shrink-0">Recent Finished Work Orders</p>
        <div class="overflow-auto flex-1 min-h-0 [&::-webkit-scrollbar]:hidden">
            <table class="w-full text-xs table-fixed">
                <thead class="sticky top-0 bg-nexora-slate-200">
                    <tr class="border-b border-nexora-corporate/30">
                        <th class="text-left text-nexora-navy-mid font-medium pb-2 w-28">Order ID</th>
                        <th class="text-left text-nexora-navy-mid font-medium pb-2">Build</th>
                        <th class="text-left text-nexora-navy-mid font-medium pb-2 w-24">Assigned</th>
                        <th class="text-left text-nexora-navy-mid font-medium pb-2 w-20">Parts OK</th>
                        <th class="text-left text-nexora-navy-mid font-medium pb-2 w-24">Status</th>
                        <th class="text-left text-nexora-navy-mid font-medium pb-2 w-28">Due / Done</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-nexora-corporate/30">
                    @foreach($recentOrders as $wo)
                        @php
                            $woReady   = collect($wo['parts'])->where('status', 'Ready')->count();
                            $woTotal   = count($wo['parts']);
                            $allReady  = $woReady === $woTotal;
                            $pill      = $statusStyles[$wo['status']]['pill'] ?? 'bg-nexora-gray/80 text-nexora-off-white';
                        @endphp
                        <tr>
                            <td class="py-2 font-mono text-nexora-navy-mid">{{ $wo['id'] }}</td>
                            <td class="py-2 text-nexora-deep-navy truncate pr-2">{{ $wo['name'] }}</td>
                            <td class="py-2 text-nexora-navy-mid">{{ $wo['assigned'] }}</td>
                            <td class="py-2 {{ $allReady ? 'text-nexora-success' : 'text-nexora-warning' }}">
                                {{ $woReady }}/{{ $woTotal }}
                            </td>
                            <td class="py-2">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $pill }}">{{ $wo['status'] }}</span>
                            </td>
                            <td class="py-2 text-nexora-navy-mid">{{ $wo['due'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Technician Order Tally --}}
    <div class="bg-nexora-slate-200 rounded-xl border border-nexora-corporate/50 p-4 w-[250px] flex flex-col min-h-0">
        <p class="text-xs font-medium text-nexora-slate-500 uppercase tracking-wider mb-3 flex-shrink-0">Orders per technician</p>
        <div class="overflow-auto flex-1 min-h-0 [&::-webkit-scrollbar]:hidden flex flex-wrap gap-6 content-start">
            @foreach($assignees as $a)
                @php $pct = $total > 0 ? round(($a['count'] / $total) * 100) : 0; @endphp
                <div class="flex items-center gap-3 min-w-[180px]">
                    <div class="w-8 h-8 rounded-full bg-nexora-corporate flex items-center justify-center text-xs font-medium text-nexora-deep-navy flex-shrink-0">
                        {{ strtoupper(substr($a['name'], 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between mb-1">
                            <span class="text-xs text-nexora-deep-navy">{{ $a['name'] }}</span>
                            <span class="text-xs text-nexora-navy-mid">{{ $a['count'] }}</span>
                        </div>
                        <div class="h-1.5 bg-nexora-slate-500/20 rounded-full overflow-hidden w-[180px]">
                            <div class="h-full bg-nexora-corporate rounded-full" style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
</div>
</div>
<script>
    window.reportsData = {
        statusLabels: @json($statusLabels),
        statusCounts: @json($statusCounts),
        statusColors: @json($statusColors),
        weekLabels:   @json($weekLabels),
        weekBuilds:   @json($weekBuilds),
        weekDefects:  @json($weekDefects),
        partsReady:   {{ $partsReady }},
        partsSourcing: {{ $partsSourcing }},
        partsMissing: {{ $partsMissing }}
    };
</script>
<script src="{{ asset('js/reports-charts.js') }}"></script>
