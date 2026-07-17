@php

$selectedIndex = (int) request()->get('order', 0);
$selectedOrder = $workOrders[$selectedIndex] ?? $workOrders[0];

@endphp
<div class="flex gap-2 h-full">

    {{-- LEFT COLUMN --}}
    <div class="w-[30%] flex-shrink-0 flex flex-col gap-2">
        <div class="flex items-center gap-4">
            <h1 class="font-heading font-medium text-xl text-nexora-navy-mid whitespace-nowrap">
                {{ $subName }}
            </h1>
            <div class="relative flex-1 max-w-[80%]">
                <x-heroicon-o-magnifying-glass class="absolute left-2.5 top-1.5 w-4 h-4 text-nexora-navy/50"/>
                <input type="text" placeholder="Search"
                        id="search-input"
                        oninput="filterOrders(currentFilter)"
                        class="w-full pl-8 pr-3 py-1.5 rounded-md bg-nexora-steel-blue/50 text-nexora-deep-navy
                              text-xs placeholder-nexora-navy/50 border border-nexora-corporate
                              focus:outline-none focus:border-nexora-deep-navy">
            </div>
        </div>
            <div class="flex gap-2 my-2" id="filter-bar">
                <button data-filter="all"
                        onclick="filterOrders('all')"
                        class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate
                            bg-nexora-corporate text-white transition-colors duration-150">
                    All
                </button>
                <button data-filter="Building"
                        onclick="filterOrders('Building')"
                        class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate
                            text-nexora-deep-navy hover:bg-nexora-corporate hover:text-white transition-colors duration-150">
                    Building
                </button>
                <button data-filter="Pending"
                        onclick="filterOrders('Pending')"
                        class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate
                            text-nexora-deep-navy hover:bg-nexora-corporate hover:text-white transition-colors duration-150">
                    Pending
                </button>
                <button data-filter="Finished"
                        onclick="filterOrders('Finished')"
                        class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate
                            text-nexora-deep-navy hover:bg-nexora-corporate hover:text-white transition-colors duration-150">
                    Finished
                </button>
                <button data-filter="QC Check"
                        onclick="filterOrders('QC Check')"
                        class="filter-btn py-1 px-2 rounded-full text-xs border border-nexora-corporate
                            text-nexora-deep-navy hover:bg-nexora-corporate hover:text-white transition-colors duration-150">
                    QC Check
                </button>
            </div>
        {{-- Card list --}}
        <div class="flex-1 rounded-lg bg-nexora-slate-200 border border-nexora-corporate/50
                    px-1 py-3 overflow-y-auto [&::-webkit-scrollbar]:hidden">
            @foreach($workOrders as $i => $order)
            @if (strtolower($order['status']) != 'cancelled')
                @php
                    $style    = $statusStyles[$order['status']] ?? ['pill' => 'bg-gray-400 text-white'];
                    $isActive = $i === $selectedIndex;
                @endphp
                <a id="card-{{ $i }}"
                    data-status="{{ $order['status'] }}"
                    data-name="{{ $order['name'] }}"
                    onclick="showOrder({{ $i }})"
                    class="block px-3 py-2.5 mb-1.5 cursor-pointer transition-all duration-150 row-animate
                          {{ $isActive
                              ? 'bg-nexora-steel-blue/80'
                              : 'hover:shadow-md hover:-translate-y-[2px] hover:bg-nexora-steel-blue/50' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] text-nexora-navy mb-0.5">{{ $order['id'] }}</p>
                            <p class="text-sm font-semibold text-nexora-deep-navy truncate">{{ $order['name'] }}</p>
                            <p class="text-[10px] text-nexora-navy mt-0.5 truncate">{{ $order['specs'] }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $style['pill'] }}">
                                {{ $order['status'] }}
                            </span>
                            <span class="text-[10px] text-nexora-navy-mid">{{ $order['due'] }}</span>
                        </div>
                    </div>
                </a>
            @endif
            @endforeach
        </div>

    </div>

    {{-- RIGHT PANEL --}}
    <div class="flex-1 bg-nexora-slate-200 border border-nexora-corporate/50 rounded-xl overflow-y-auto [&::-webkit-scrollbar]:hidden text-nexora-deep-navy">
    @foreach($workOrders as $i => $order)
        @php
            $selStyle      = $statusStyles[$order['status']] ?? ['pill' => 'bg-gray-300 text-gray-800'];
            $Total        = count($order['parts']);
            $Ready        = collect($order['parts'])->where('status', 'Ready')->count();
            $Sourcing     = collect($order['parts'])->where('status', 'Sourcing')->count();
            $Missing      = collect($order['parts'])->where('status', 'Missing')->count();
            $Pct          = $Total > 0 ? round(($Ready / $Total) * 100) : 0;
        @endphp

        <div id="detail-{{ $i }}" class="p-5 {{ $i === 0 ? '' : 'hidden' }}">
            <span class="text-xs text-nexora-navy mb-1">{{ $order['id'] }} &bull; {{ $order['source'] }}</span>
            <h2 class="text-2xl font-bold mb-2">{{ $order['name'] }}</h2>
            <div class="flex items-center gap-2 mb-5">
                <span class="px-2.5 py-1.5 rounded-full text-xs font-bold {{ $selStyle['pill'] }}">
                    {{ $order['status'] }}
                </span>
                <span class="text-xs text-nexora-navy">Assigned: {{ $order['assigned'] }}</span>
            </div>
            <div class="rounded-xl h-full bg-nexora-steel-blue overflow-auto [&::-webkit-scrollbar]:hidden mt-8 p-4 pt-2">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 bg-nexora-steel-blue">
                        <tr>
                            <th class="table-header px-8 text-center text-nexora-deep-navy">Product ID</th>
                            <th class="table-header px-8 text-center text-nexora-deep-navy">Product Name</th>
                            <th class="table-header px-8 text-center text-nexora-deep-navy">Product Prize</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    @endforeach        
    </div>
</div>

<script>initRowAnimations();</script>
