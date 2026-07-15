@php
    $qcTemplates = $tempData['qcTemplates'];
    $qcSessions  = collect($tempData['qcSessions'] ?? []);

    // Only QC Check orders appear in the picker
    $qcOrders    = collect($workOrders)->where('status', 'QC Check')->values();
    $selectedIdx = (int) request()->get('qcorder', 0);
    $selectedOrder = $qcOrders[$selectedIdx] ?? $qcOrders[0] ?? null;

    // Detect template from build name
    $templateKey = 'gaming';
    if ($selectedOrder) {
        $n = strtolower($selectedOrder['name']);
        if (str_contains($n, 'workstation') || str_contains($n, 'ultra'))   $templateKey = 'workstation';
        elseif (str_contains($n, 'office') || str_contains($n, 'mini'))     $templateKey = 'office';
        elseif (str_contains($n, 'budget') || str_contains($n, 'student'))  $templateKey = 'budget';
    }

    $checks  = $qcTemplates[$templateKey] ?? [];
    $session = $qcSessions->firstWhere('woId', $selectedOrder['id'] ?? '');
    $results = collect($session['results'] ?? []);

    // Summary counts
    $totalChecks = count($checks);
    $passCount   = $results->where('verdict', 'Pass')->count();
    $warnCount   = $results->where('verdict', 'Warn')->count();
    $failCount   = $results->where('verdict', 'Fail')->count();
    $doneCount   = $results->filter(fn($r) => $r['verdict'] !== '')->count();
    $pct         = $totalChecks > 0 ? round(($doneCount / $totalChecks) * 100) : 0;

    // Flagged issues (Warn or Fail with a note)
    $checkMap = collect($checks)->keyBy('id');
    $flagged  = $results->filter(fn($r) => in_array($r['verdict'], ['Warn','Fail']) && $r['note'] !== '');
@endphp

<div class="flex gap-3 h-full">

    {{-- ── LEFT: QC order picker ─────────────────────────────────────────────--}}
    <div class="w-44 flex-shrink-0 flex flex-col gap-2">
        <h1 class="font-heading font-medium text-xl text-nexora-navy-mid whitespace-nowrap">BENCHMARK</h1>

        <div class="flex-1 rounded-lg bg-nexora-slate-200 border border-nexora-corporate/50
                    px-1 py-3 overflow-y-auto [&::-webkit-scrollbar]:hidden">
            @forelse($qcOrders as $i => $order)
                @php
                    $sess    = $qcSessions->firstWhere('woId', $order['id']);
                    $res     = collect($sess['results'] ?? []);
                    $hasFail = $res->where('verdict', 'Fail')->count() > 0;
                    $hasWarn = $res->where('verdict', 'Warn')->count() > 0;
                    $dot     = $hasFail ? 'bg-nexora-danger' : ($hasWarn ? 'bg-nexora-warning' : 'bg-nexora-success');
                    $isActive = $i === $selectedIdx;
                @endphp
                <a href="?page=qc&sub=benchmark&qcorder={{ $i }}"
                   class="block px-3 py-2.5 mb-1 rounded-md cursor-pointer transition-all duration-150
                          {{ $isActive
                              ? 'bg-nexora-steel-blue/80'
                              : 'hover:shadow-md hover:-translate-y-[2px] hover:bg-nexora-steel-blue/50' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] text-nexora-navy mb-0.5 font-['Courier_New']">{{ $order['id'] }}</p>
                            <p class="text-xs font-semibold text-nexora-deep-navy truncate">{{ $order['name'] }}</p>
                            <p class="text-[10px] text-nexora-navy-mid mt-0.5">{{ $order['assigned'] }}</p>
                        </div>
                        <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1 {{ $dot }}"></span>
                    </div>
                </a>
            @empty
                <p class="text-xs text-nexora-navy-mid px-3 py-2">No orders in QC check.</p>
            @endforelse
        </div>
    </div>

    {{-- ── RIGHT: Checklist + Side panel ────────────────────────────────────--}}
    @if($selectedOrder)
    <div class="flex flex-1 gap-3 min-w-0">

        {{-- Checklist table --}}
        <div class="flex flex-col gap-2 flex-wrap">

            {{-- Order header --}}
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] text-nexora-navy font-['Courier_New']">
                        {{ $selectedOrder['id'] }} &bull; {{ $selectedOrder['source'] }}
                    </p>
                    <h2 class="text-xl font-bold text-nexora-deep-navy leading-tight">{{ $selectedOrder['name'] }}</h2>
                    <p class="text-xs text-nexora-navy-mid mt-0.5">
                        {{ $selectedOrder['specs'] }} &bull; Tech: {{ $selectedOrder['assigned'] }}
                    </p>
                </div>

                {{-- Button alone on the right --}}
                <button onclick="openBenchmarkModal()"
                        class="flex-shrink-0 px-4 py-1.5 rounded-full text-xs font-semibold
                            border border-nexora-corporate bg-nexora-corporate text-white
                            hover:bg-nexora-navy-mid transition-colors duration-150 whitespace-nowrap">
                    Enter Results
                </button>
            </div>

            {{-- Pills + progress bar on their own row --}}
            <div class="flex items-center gap-3">
                <div class="flex gap-1.5 flex-shrink-0">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-nexora-success/80 text-nexora-off-white whitespace-nowrap">
                        {{ $passCount }} Pass
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-nexora-warning/80 text-nexora-off-white whitespace-nowrap">
                        {{ $warnCount }} Warn
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-nexora-danger/80 text-nexora-off-white whitespace-nowrap">
                        {{ $failCount }} Fail
                    </span>
                </div>
                <div class="flex-1 h-1.5 bg-nexora-slate-500/20 rounded-full overflow-hidden">
                    <div class="h-full bg-nexora-corporate rounded-full transition-all duration-300"
                        style="width:{{ $pct }}%"></div>
                </div>
                <p class="text-[10px] text-nexora-navy-mid flex-shrink-0 whitespace-nowrap">
                    {{ $doneCount }}/{{ $totalChecks }} checked
                </p>
            </div>

            {{-- Table --}}
            <div class="flex-1 rounded-xl bg-nexora-slate-200 border border-nexora-corporate/50
                        overflow-y-auto [&::-webkit-scrollbar]:hidden">
                <table class="w-full text-xs table-fixed">
                    <thead class="sticky top-0 bg-nexora-slate-200 z-10">
                        <tr class="border-b border-nexora-corporate/30">
                            <th class="text-left text-nexora-deep-navy font-medium px-4 py-2.5 w-7">#</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-4 py-2.5">Benchmark / Check</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-4 py-2.5 w-28">Tool</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-4 py-2.5 w-32">Target</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-4 py-2.5 w-32">Result</th>
                            <th class="text-left text-nexora-deep-navy font-medium px-4 py-2.5 w-20">Verdict</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowNum = 1; $lastCat = ''; @endphp
                        @foreach($checks as $check)
                            @php
                                $res     = $results->firstWhere('checkId', $check['id']);
                                $val     = $res['value'] ?? null;
                                $verdict = $res['verdict'] ?? '';
                                $note    = $res['note'] ?? '';

                                // Auto-compute verdict if value exists but verdict empty
                                if ($val !== null && $verdict === '') {
                                    if ($check['operator'] === '>=')
                                        $verdict = $val >= $check['target'] ? 'Pass' : ($val >= $check['target'] * 0.9 ? 'Warn' : 'Fail');
                                    elseif ($check['operator'] === '<=')
                                        $verdict = $val <= $check['target'] ? 'Pass' : ($val <= $check['target'] * 1.1 ? 'Warn' : 'Fail');
                                    else
                                        $verdict = $val == $check['target'] ? 'Pass' : 'Fail';
                                }

                                $vPill = match($verdict) {
                                    'Pass'  => 'bg-nexora-success/80 text-nexora-off-white',
                                    'Warn'  => 'bg-nexora-warning/80 text-nexora-off-white',
                                    'Fail'  => 'bg-nexora-danger/80 text-nexora-off-white',
                                    default => 'bg-nexora-slate-500/30 text-nexora-navy-mid',
                                };
                                $valColor = match($verdict) {
                                    'Pass'  => 'text-nexora-success',
                                    'Warn'  => 'text-nexora-warning',
                                    'Fail'  => 'text-nexora-danger',
                                    default => 'text-nexora-navy-mid',
                                };
                                $showCat = $check['category'] !== $lastCat;
                                $lastCat = $check['category'];
                            @endphp

                            {{-- Category divider row --}}
                            @if($showCat)
                                <tr class="bg-nexora-slate-500/10">
                                    <td colspan="6" class="px-4 py-1.5 text-[10px] font-semibold
                                                           text-nexora-corporate uppercase tracking-wider">
                                        {{ $check['category'] }}
                                    </td>
                                </tr>
                            @endif

                            <tr class="border-b border-nexora-corporate/10
                                       hover:bg-nexora-steel-blue/30 transition-colors duration-150">
                                <td class="px-4 py-2.5 text-nexora-navy-mid font-['Courier_New']">
                                    {{ str_pad($rowNum++, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <p class="font-medium text-nexora-deep-navy">{{ $check['name'] }}</p>
                                    @if($note)
                                        <p class="text-[10px] text-nexora-warning mt-0.5 italic">{{ $note }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-nexora-navy-mid">{{ $check['tool'] }}</td>
                                <td class="px-4 py-2.5 text-nexora-navy-mid">
                                    {{ $check['operator'] }}
                                    {{ in_array($check['unit'], ['pts','MB/s','MT/s']) ? number_format($check['target']) : $check['target'] }}
                                    {{ $check['unit'] !== 'pass' ? $check['unit'] : '' }}
                                </td>
                                <td class="px-4 py-2.5 font-medium font-['Courier_New'] {{ $valColor }}">
                                    @if($val !== null)
                                        {{ in_array($check['unit'], ['pts','MB/s','MT/s']) ? number_format($val) : $val }}
                                        {{ !in_array($check['unit'], ['pass']) ? $check['unit'] : '' }}
                                    @else
                                        <span class="text-nexora-navy-mid opacity-40">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @if($verdict)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $vPill }}">
                                            {{ $verdict }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-nexora-navy-mid opacity-50">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Side panel ──────────────────────────────────────────────────--}}
        <div class="w-52 flex-shrink-0 flex flex-col gap-3">

            {{-- Build info --}}
            <div class="bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">Build Info</p>
                @foreach([
                    ['Due',      $selectedOrder['due']],
                    ['Template', ucfirst($templateKey)],
                    ['Checks',   $totalChecks . ' total'],
                    ['Done',     $doneCount . ' / ' . $totalChecks . ' (' . $pct . '%)'],
                ] as [$k, $v])
                    <div class="flex justify-between items-center py-1.5 border-b border-nexora-corporate/20 last:border-0">
                        <span class="text-[10px] text-nexora-navy-mid">{{ $k }}</span>
                        <span class="text-[10px] font-medium text-nexora-deep-navy">{{ $v }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Flagged issues --}}
            @if($flagged->count())
            <div class="bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">
                    Flagged Issues
                </p>
                <div class="flex flex-col gap-2">
                    @foreach($flagged as $flag)
                        @php
                            $chk = $checkMap[$flag['checkId']] ?? null;
                            $fc  = $flag['verdict'] === 'Fail'
                                    ? 'border-nexora-danger/40 bg-nexora-danger/10'
                                    : 'border-nexora-warning/40 bg-nexora-warning/10';
                            $ft  = $flag['verdict'] === 'Fail'
                                    ? 'text-nexora-danger'
                                    : 'text-nexora-warning';
                        @endphp
                        <div class="rounded-lg border px-2.5 py-2 {{ $fc }}">
                            <p class="text-[10px] font-semibold {{ $ft }}">{{ $chk['name'] ?? $flag['checkId'] }}</p>
                            <p class="text-[10px] text-nexora-navy-mid mt-0.5 leading-relaxed">{{ $flag['note'] }}</p>
                        </div>
                    @endforeach
                </div>
                <button class="mt-3 w-full py-1.5 rounded-lg text-[10px] font-semibold
                               border border-nexora-danger/50 bg-nexora-danger/10 text-nexora-danger
                               hover:bg-nexora-danger/20 transition-colors duration-150">
                    Send to Procurement
                </button>
            </div>
            @endif

            {{-- Defect escalation flow --}}
            <div class="bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl p-4">
                <p class="text-[10px] font-semibold text-nexora-deep-navy uppercase tracking-wider mb-3">
                    Defect Flow
                </p>
                @php
                    $steps = [
                        ['QC flagged',         'Technician marks issue',         $flagged->count() > 0],
                        ['Sent to procurement','Defect report + part info',       false],
                        ['Procurement reviews','Return, replace, or reorder',     false],
                        ['Part replaced',      'WO resumes or rework issued',     false],
                        ['QC re-check',        'Full checklist re-run',           false],
                    ];
                @endphp
                <div class="flex flex-col">
                    @foreach($steps as $si => [$sname, $ssub, $sdone])
                        <div class="flex gap-2 items-start">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-semibold
                                    {{ $sdone
                                        ? 'bg-nexora-success/20 text-nexora-success border border-nexora-success/50'
                                        : 'bg-nexora-slate-500/20 text-nexora-navy-mid border border-nexora-corporate/30' }}">
                                    {{ $sdone ? '✓' : ($si + 1) }}
                                </div>
                                @if($si < count($steps) - 1)
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

            {{-- Submit --}}
            <button class="w-full py-2 rounded-xl text-xs font-semibold
                           border border-nexora-corporate bg-nexora-corporate text-white
                           hover:bg-nexora-navy-mid transition-colors duration-150">
                Submit QC Report
            </button>

        </div>
    </div>
    @else
        <div class="flex-1 flex items-center justify-center text-nexora-navy-mid text-sm">
            No orders currently in QC Check.
        </div>
    @endif
    {{-- ── BACKDROP ── --}}
    <div id="benchmark-backdrop"
        class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center hidden"
        onclick="handleBackdropClick(event, 'benchmark-backdrop')">
    
        {{-- Blur overlay --}}
        <div class="absolute inset-0 bg-nexora-deep-navy/40 backdrop-blur-sm pointer-events-none"></div>
    
        {{-- Modal --}}
        <div onclick="event.stopPropagation()"
            class="relative z-10 bg-nexora-off-white border border-nexora-corporate/50 rounded-2xl
                    shadow-2xl w-full max-w-2xl mx-4 max-h-[85vh] flex flex-col">
    
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 pt-5 pb-3
                        border-b border-nexora-corporate/20 flex-shrink-0">
                <div>
                    <p class="text-[10px] text-nexora-navy-mid mb-0.5 font-['Courier_New']">
                        {{ $selectedOrder['id'] }} &bull; Enter / update benchmark results
                    </p>
                    <h2 class="text-lg font-bold text-nexora-deep-navy">{{ $selectedOrder['name'] }}</h2>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Live counts --}}
                    <div class="flex gap-1.5" id="bm-live-counts">
                        <span id="bm-count-pass"
                            class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-nexora-success/80 text-white">
                            0 Pass
                        </span>
                        <span id="bm-count-warn"
                            class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-nexora-warning/80 text-white">
                            0 Warn
                        </span>
                        <span id="bm-count-fail"
                            class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-nexora-danger/80 text-white">
                            0 Fail
                        </span>
                    </div>
                    <button onclick="closeModal('benchmark-backdrop')"
                            class="w-7 h-7 rounded-full flex items-center justify-center text-nexora-navy-mid
                                hover:bg-nexora-slate-500/20 hover:text-nexora-deep-navy transition-colors text-lg leading-none">
                        ✕
                    </button>
                </div>
            </div>
    
            {{-- Body: one row per check --}}
            <div class="flex-1 overflow-y-auto [&::-webkit-scrollbar]:hidden px-5 py-3">
                <div class="flex flex-col gap-2" id="bm-check-list">
                </div>
            </div>
    
            {{-- Footer --}}
            <div class="flex items-center justify-between px-5 py-3
                        border-t border-nexora-corporate/20 flex-shrink-0">
                <p id="bm-save-msg" class="text-xs text-nexora-success hidden">✓ Results saved</p>
                <div class="flex gap-2 ml-auto">
                    <button onclick="closeModal('benchmark-backdrop')"
                            class="px-4 py-1.5 rounded-full text-xs font-medium border border-nexora-corporate/50
                                text-nexora-navy-mid hover:bg-nexora-slate-200 transition-colors">
                        Cancel
                    </button>
                    <button onclick="saveBenchmarkResults()"
                            class="px-4 py-1.5 rounded-full text-xs font-semibold bg-nexora-corporate text-white
                                hover:bg-nexora-navy-mid transition-colors">
                        Save Results
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const benchmarkData = {
            woId:      "{{ $selectedOrder['id'] }}",
            checks:    @json($checks),
            results:   @json($results->values()),
            orderName: "{{ $selectedOrder['name'] }}",
        };
    </script>
</div>
