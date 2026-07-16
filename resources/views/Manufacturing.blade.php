<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nexora – Manufacturing</title>
    <link rel="icon" type="image/png" href="images/Nexora_Logo_Transparent.png">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/shared.js') }}"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'nexora': {
                            'deep-navy':  '#0B1E3D',
                            'navy':       '#132B52',
                            'navy-mid':   '#1B3A6B',
                            'corporate':  '#1B6FC8',

                            'sky':        '#4A9EE8',
                            'light-blue': '#7BBEF0',
                            'ice':        '#D6ECFC',
                            'steel-blue': '#869FB1',

                            'off-white':  '#F4F6FA',
                            'slate-200':  '#E2E8F0',
                            'slate-500':  '#5B7A9D',
                            'white':      '#FFFFFF',
                            'gray':       '#9D9D9D',

                            'success':    '#16A34A',
                            'caution':    '#FBD035',
                            'warning':    '#D97706',
                            'danger':     '#DC2626',
                            'info':       '#0EA5E9',

                            'stat-green' :'#15803D',
                            'stat-yellow':'#cda101',
                            'stat-orange':'#92400E',
                            'stat-red':   '#991B1B',
                            'stat-blue':  '#1E40AF',
                        }
                    },
                    fontFamily: {
                        'heading': ['Inter Medium', 'sans-serif'],
                        'body':    ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background-color: #1B3A6B; }
        html, body { height: 100%; }
    </style>
    @php
        $tempData = app(\App\Services\ManufacturingDataService::class)->loadAll();

        $workOrders   = $tempData['workOrders'];
        $workers      = $tempData['workers'];
        $statusStyles = config('nexora.statusStyles');
        $partStyles   = config('nexora.partStyles');
        $qcTemplates  = $tempData['qcTemplates'];
        $qcSessions   = $tempData['qcSessions'];
        $reworkOrders = $tempData['reworkOrders'];
    @endphp
</head>
<body class="font-body text-white flex flex-col h-full">

    {{-- Top Navbar --}}
    <header class="w-full bg-nexora-deep-navy shadow-md shadow-nexora-deep-navy flex items-center px-6 py-3 flex-shrink-0">
        {{-- Logo --}}
        <div class="flex items-center gap-1">
            <img src="{{ asset('images/Banner Transparent.png') }}" alt="Application Logo" class="w-56">
            <img src="{{ asset('images/techforge.png') }}" alt="Application Logo" class="w-20">
        </div>

        {{-- Nav Links --}}
        <div class="flex items-center justify-end gap-1 flex-1">
            @php
                $navItems = [
                    ['label' => 'Dashboard',         'href' => '?page=dashboard', 'page' => 'dashboard', 'active' => request()->get('page', 'dashboard') === 'dashboard'],
                    ['label' => 'Work Orders',       'href' => '?page=orders',    'page' => 'orders',    'active' => request()->get('page') === 'orders'],
                    ['label' => 'Quality Check', 'href' => '?page=qc',   'page' => 'qc',   'active' => request()->get('page') === 'qc'],
                    ['label' => 'Reports',           'href' => '?page=reports',   'page' => 'reports',   'active' => request()->get('page') === 'reports'],
                ];
                $curPage = request()->get('page', 'dashboard');
                $curSub  = request()->get('sub', '');
            @endphp

            @foreach($navItems as $item)
                <a href="{{ $item['href'] }}"
                   class="m-2 font-black transition-colors duration-300
                          {{ $item['active']
                              ? 'font-bold text-nexora-off-white'
                              : 'font-thin text-nexora-corporate hover:text-nexora-ice' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Profile circle --}}
        <div class="flex items-center justify-end w-[5vw]">
            <a href="/signin" class="w-9 h-9 rounded-full bg-white flex-shrink-0"></a>
        </div>
    </header>

    {{-- Sidebar/Main --}}
    <div class="flex flex-1 overflow-hidden gap-1 max-h-[98%] max-w-[99%] m-4">
    @if($curPage != 'dashboard' && $curPage != 'reports')
        {{-- Sidebar --}}
        <aside class="w-44 bg-nexora-off-white border-[1px] border-nexora-corporate flex flex-col flex-shrink-0 rounded-lg max-w-full min-h-full mx-auto ml-1">
            <nav class="flex-1 flex flex-col pt-[40%] px-3 space-y-0.5 text-sm">
                {{-- Quality Check Sub Tabs --}}
                @if($curPage === 'qc')
                    <script src="{{ asset('js/benchmark.js') }}"></script>
                    @php
                        $qcSubs = [
                            ['label' => 'Benchmark',  'sub' => 'benchmark'],
                            ['label' => 'Rework',     'sub' => 'rework'],
                            ['label' => 'Analytics',  'sub' => 'analytics'],
                        ];
                    @endphp
                    @foreach($qcSubs as $tab)
                        <a href="?page=qc&sub={{ $tab['sub'] }}"
                           class="block px-3 py-2 rounded-md font-medium transition-colors duration-150
                                  {{ ($curSub === $tab['sub'] || ($curSub === '' && $tab['sub'] === 'benchmark'))
                                      ? 'bg-nexora-sky text-white'
                                      : 'text-nexora-slate-500 hover:bg-nexora-light-blue hover:text-white' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                @endif

                {{-- Work Orders Sub Tabs --}}
                @if($curPage === 'orders')
                    <script src="{{ asset('js/status.js') }}"></script>
                    <script src="{{ asset('js/assignment.js') }}"></script>
                    <script src="{{ asset('js/schedule.js') }}"></script>
                    @php
                        $orderSubs = [
                            ['label' => 'All Orders', 'sub' => 'all'],
                            ['label' => 'Status', 'sub' => 'status'],
                            ['label' => 'Schedule',   'sub' => 'schedule'],
                            ['label' => 'BOMs',       'sub' => 'boms'],
                            ['label' => 'Assignment', 'sub' => 'assignment'],
                        ];
                    @endphp

                    @foreach($orderSubs as $tab)
                        <a href="?page=orders&sub={{ $tab['sub'] }}"
                           class="block px-3 py-2 rounded-md font-medium transition-colors duration-150
                                  {{ ($curSub === $tab['sub'] || ($curSub === '' && $tab['sub'] === 'all'))
                                      ? 'bg-nexora-sky text-white'
                                      : 'text-nexora-slate-500 hover:bg-nexora-light-blue hover:text-white' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                @endif

            </nav>
        </aside>
    @endif
        {{-- Main Content --}}
        <main class="flex flex-col h-full h-full mx-auto w-full">
            <div class="flex-1 h-full p-4 bg-nexora-off-white border-[1px] border-nexora-corporate rounded-lg overflow-y-auto [&::-webkit-scrollbar]:hidden">
                {{-- Dashboard --}}
                @if($curPage === 'dashboard')
                    @include('partials.dashboard')
                    {{-- Reports --}}
                @elseif($curPage === 'reports')
                    @include('partials.reports')            
                {{-- Work Orders --}}
                @elseif($curPage === 'orders')
                @php
                    $subName = 'All Orders';

                    foreach ($orderSubs as $tab) {
                        if ($tab['sub'] === $curSub) {
                            $subName = $tab['label'];
                            break;
                        }
                    }
                @endphp
                    {{-- All Orders --}}
                    @if($curSub === 'all' || $curSub === '')
                        @include('partials.workorder.allorder')
                    {{-- Status --}}
                    @elseif ($curSub === 'status')
                        @include('partials.workorder.status')
                    {{-- Schedule --}}
                    @elseif($curSub === 'schedule')
                        @include('partials.workorder.schedule')
                    {{-- BOMs --}}
                    @elseif($curSub === 'boms')
                            @include('partials.workorder.bom')
                    {{-- Assignment --}}
                    @elseif($curSub === 'assignment')
                        @include('partials.workorder.assignment')
                    @endif

                {{-- Quality Check --}}
                @elseif($curPage === 'qc')
                    @if($curSub === 'benchmark' || $curSub === '')
                        @include('partials.Quality Check.benchmark')
                    @elseif($curSub === 'rework')
                        @include('partials.Quality Check.rework')
                    @elseif($curSub === 'analytics')
                        @include('partials.Quality Check.analytics')
                    @endif

                @endif
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</body>
</html>