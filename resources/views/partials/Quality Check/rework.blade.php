@php
    $reworkOrders   = collect($tempData['reworkOrders'] ?? []);
    $selectedIdx    = (int) request()->get('rework', 0);
    $selectedRework = $reworkOrders[$selectedIdx] ?? $reworkOrders[0] ?? null;

    // Status pill map
    $reworkPill = fn($s) => match($s) {
        'Waiting for Part' => 'bg-nexora-warning/80 text-nexora-off-white',
        'In Rework'        => 'bg-nexora-info/80 text-nexora-off-white',
        'Ready for QC'     => 'bg-nexora-success/80 text-nexora-off-white',
        'Escalated'        => 'bg-nexora-danger/80 text-nexora-off-white',
        default            => 'bg-nexora-slate-500/30 text-nexora-navy-mid',
    };
    $priorityColor = fn($p) => match($p) {
        'High'   => 'text-nexora-danger',
        'Medium' => 'text-nexora-warning',
        'Low'    => 'text-nexora-success',
        default  => 'text-nexora-navy-mid',
    };
    $partPill = fn($s) => match($s) {
        'Ready'    => 'bg-nexora-success/80 text-nexora-off-white',
        'Sourcing' => 'bg-nexora-warning/80 text-nexora-off-white',
        'Missing'  => 'bg-nexora-danger/80 text-nexora-off-white',
        default    => 'bg-nexora-slate-500/30 text-nexora-navy-mid',
    };
@endphp

<div class="flex gap-3 h-full">

    {{-- ── LEFT: Rework order picker ─────────────────────────────────────────--}}
    <div class="w-44 flex-shrink-0 flex flex-col gap-2">
        <h1 class="font-heading font-medium text-xl text-nexora-navy-mid whitespace-nowrap">REWORK</h1>

        <div class="flex-1 rounded-lg bg-nexora-slate-200 border border-nexora-corporate/50
                    px-1 py-3 overflow-y-auto [&::-webkit-scrollbar]:hidden">
            @forelse($reworkOrders as $i => $rw)
                @php $isActive = $i === $selectedIdx; @endphp
                <a href="?page=qc&sub=rework&rework={{ $i }}"
                   class="block px-3 py-2.5 mb-1 rounded-md cursor-pointer transition-all duration-150
                          {{ $isActive
                              ? 'bg-nexora-steel-blue/80'
                              : 'hover:bg-nexora-steel-blue/50 hover:shadow-md hover:-translate-y-[2px]' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] text-nexora-navy font-['Courier_New'] mb-0.5">{{ $rw['id'] }}</p>
                            <p class="text-xs font-semibold text-nexora-deep-navy truncate">{{ $rw['buildName'] }}</p>
                            <p class="text-[10px] text-nexora-navy-mid mt-0.5">{{ $rw['assignedTech'] }}</p>
                        </div>
                        <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full flex-shrink-0 mt-0.5 {{ $reworkPill($rw['status']) }}">
                            {{ explode(' ', $rw['status'])[0] }}
                        </span>
                    </div>
                </a>
            @empty
                <p class="text-xs text-nexora-navy-mid px-3 py-2">No rework orders.</p>
            @endforelse
        </div>
    </div>

    {{-- ── RIGHT: Detail + Side panel ───────────────────────────────────────--}}
    @if($selectedRework)
    <div class="flex flex-1 gap-3 min-w-0">

        {{-- Main detail --}}
        <div class="flex-1 flex flex-col gap-3 min-w-0">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-3 flex-wrap flex-shrink-0">
                <div>
                    <p class="text-[10px] text-nexora-navy font-['Courier_New']">
                        {{ $selectedRework['id'] }} &bull; from {{ $selectedRework['woId'] }}
                    </p>
                    <h2 class="text-xl font-bold text-nexora-deep-navy leading-tight">
                        {{ $selectedRework['buildName'] }}
                    </h2>
                    <p class="text-xs text-nexora-navy-mid mt-0.5">
                        Tech: {{ $selectedRework['assignedTech'] }}
                        &bull; Raised: {{ $selectedRework['raisedDate'] }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-xs font-semibold {{ $priorityColor($selectedRework['priority']) }}">
                        {{ $selectedRework['priority'] }} priority
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $reworkPill($selectedRework['status']) }}">
                        {{ $selectedRework['status'] }}
                    </span>
                </div>
            </div>

            {{-- Failed checks --}}
            <div class="bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4 flex-shrink-0">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">
                    Failed / Warned Benchmark Checks
                </p>
                <table class="w-full text-xs table-fixed">
                    <thead>
                        <tr class="border-b border-nexora-corporate/30">
                            <th class="text-left text-nexora-deep-navy font-medium px-3 py-2">Check</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-3 py-2 w-28">Result</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-3 py-2 w-28">Target</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-3 py-2 w-20">Verdict</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-3 py-2">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($selectedRework['failedChecks'] as $fc)
                            @php
                                $vPill = $fc['verdict'] === 'Fail'
                                    ? 'bg-nexora-danger/80 text-nexora-off-white'
                                    : 'bg-nexora-warning/80 text-nexora-off-white';
                            @endphp
                            <tr class="border-b border-nexora-corporate/10 hover:bg-nexora-steel-blue/20 transition-colors duration-150">
                                <td class="px-3 py-2.5 font-medium text-nexora-deep-navy">{{ $fc['checkName'] }}</td>
                                <td class="px-3 py-2.5 font-['Courier_New']
                                           {{ $fc['verdict'] === 'Fail' ? 'text-nexora-danger' : 'text-nexora-warning' }}">
                                    {{ $fc['result'] }}
                                </td>
                                <td class="px-3 py-2.5 text-nexora-navy-mid">{{ $fc['target'] }}</td>
                                <td class="px-3 py-2.5">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $vPill }}">
                                        {{ $fc['verdict'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-nexora-navy-mid italic">{{ $fc['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Required parts --}}
            <div class="bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4 flex-shrink-0">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">
                    Parts Required for Rework
                </p>
                @if(count($selectedRework['requiredParts']) > 0)
                    <div class="flex flex-col gap-2">
                        @foreach($selectedRework['requiredParts'] as $part)
                            <div class="flex items-center justify-between gap-3 px-3 py-2.5
                                        bg-nexora-slate-200 border border-nexora-corporate/20 rounded-lg
                                        hover:bg-nexora-steel-blue/20 transition-colors duration-150">
                                <p class="text-xs font-medium text-nexora-deep-navy">{{ $part['name'] }}</p>
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    @if(isset($part['eta']))
                                        <p class="text-[10px] text-nexora-navy-mid">ETA: {{ $part['eta'] }}</p>
                                    @endif
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $partPill($part['status']) }}">
                                        {{ $part['status'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-nexora-navy-mid">
                        No replacement parts needed — rework is software or configuration only.
                    </p>
                @endif
            </div>

            {{-- Technician notes --}}
            <div class="flex-1 bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">
                    Technician Notes
                </p>
                <p class="text-xs text-nexora-navy-mid leading-relaxed">{{ $selectedRework['notes'] }}</p>
            </div>
        </div>

        {{-- ── Side panel ──────────────────────────────────────────────────--}}
        <div class="w-52 flex-shrink-0 flex flex-col gap-3">

            {{-- Rework info --}}
            <div class="bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">Rework Info</p>
                @foreach([
                    ['Rework ID',  $selectedRework['id']],
                    ['Work Order', $selectedRework['woId']],
                    ['Raised by',  $selectedRework['raisedBy']],
                    ['Date raised',$selectedRework['raisedDate']],
                    ['Priority',   $selectedRework['priority']],
                    ['Status',     $selectedRework['status']],
                ] as [$k, $v])
                    <div class="flex justify-between items-center py-1.5 border-b border-nexora-corporate/20 last:border-0">
                        <span class="text-[10px] text-nexora-navy-mid">{{ $k }}</span>
                        <span class="text-[10px] font-medium text-nexora-deep-navy">{{ $v }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Procurement status --}}
            <div class="bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">
                    Procurement
                </p>
                @if($selectedRework['escalatedToProcurement'])
                    <div class="rounded-lg border border-nexora-info/40 bg-nexora-info/10 px-2.5 py-2 mb-3">
                        <p class="text-[10px] font-semibold text-nexora-info">Escalated</p>
                        <p class="text-[10px] text-nexora-navy-mid mt-0.5">Defect report sent to procurement team.</p>
                    </div>
                @else
                    <div class="rounded-lg border border-nexora-corporate/30 bg-nexora-slate-500/10 px-2.5 py-2 mb-3">
                        <p class="text-[10px] text-nexora-navy-mid">Not yet escalated — no replacement parts needed.</p>
                    </div>
                @endif
                @if(count($selectedRework['requiredParts']) > 0)
                    @php
                        $allPartsReady = collect($selectedRework['requiredParts'])->every(fn($p) => $p['status'] === 'Ready');
                    @endphp
                    <p class="text-[10px] text-nexora-navy-mid mb-2">Parts status:</p>
                    @foreach($selectedRework['requiredParts'] as $part)
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] text-nexora-navy-mid truncate pr-2">{{ explode(' (', $part['name'])[0] }}</span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $partPill($part['status']) }} flex-shrink-0">
                                {{ $part['status'] }}
                            </span>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Rework flow --}}
            <div class="bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">
                    Rework Flow
                </p>
                @php
                    $flowStatus = $selectedRework['status'];
                    $flowSteps = [
                        ['QC failed / warned',    'Benchmark flags an issue',       true],
                        ['Rework order raised',   'Sent from QC benchmark',         true],
                        ['Waiting for part',      'Procurement sourcing part',       $flowStatus === 'Waiting for Part' || $flowStatus === 'In Rework' || $flowStatus === 'Ready for QC'],
                        ['In rework',             'Tech fixes or replaces part',     $flowStatus === 'In Rework' || $flowStatus === 'Ready for QC'],
                        ['Ready for QC recheck',  'Full benchmark re-run',           $flowStatus === 'Ready for QC'],
                    ];
                @endphp
                <div class="flex flex-col">
                    @foreach($flowSteps as $si => [$sname, $ssub, $sdone])
                        <div class="flex gap-2 items-start">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-semibold
                                    {{ $sdone
                                        ? 'bg-nexora-success/20 text-nexora-success border border-nexora-success/50'
                                        : 'bg-nexora-slate-500/20 text-nexora-navy-mid border border-nexora-corporate/30' }}">
                                    {{ $sdone ? '✓' : ($si + 1) }}
                                </div>
                                @if($si < count($flowSteps) - 1)
                                    <div class="w-px h-4 bg-nexora-corporate/20 my-0.5"></div>
                                @endif
                            </div>
                            <div class="pt-0.5 pb-3">
                                <p class="text-[10px] font-semibold text-nexora-deep-navy">{{ $sname }}</p>
                                <p class="text-[10px] text-nexora-navy-mid mt-0.5">{{ $ssub }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Mark ready button --}}
            <button class="w-full py-2 rounded-xl text-xs font-semibold
                           border border-nexora-corporate bg-nexora-corporate text-white
                           hover:bg-nexora-navy-mid transition-colors duration-150">
                Mark Ready for QC
            </button>

        </div>
    </div>
    @else
        <div class="flex-1 flex items-center justify-center text-nexora-navy-mid text-sm">
            No rework orders at the moment.
        </div>
    @endif
</div>
