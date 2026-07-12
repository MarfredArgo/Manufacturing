@php

$today = \Carbon\Carbon::now();
$projectsWithPriority = collect($workOrders)->map(function ($order) use ($today) {
    $rawDue = preg_replace('/^(Due|Completed)\s+/i', '', $order['due']);
    $dueDate = \Carbon\Carbon::parse($rawDue);
    $daysDiff = $today->diffInDays($dueDate, false); // negative = overdue

    if ($order['status'] === 'Finished') {
        $priority = 'completed';
        $priorityLabel = 'Completed';
        $priorityClass = 'bg-nexora-corporate text-white';
    } elseif ($daysDiff < 0) {
        $priority = 'overdue';
        $priorityLabel = 'Overdue';
        $priorityClass = 'bg-red-600 text-white';
    } elseif ($daysDiff <= 3) {
        $priority = 'high';
        $priorityLabel = 'High';
        $priorityClass = 'bg-red-500 text-white';
    } elseif ($daysDiff <= 7) {
        $priority = 'medium';
        $priorityLabel = 'Medium';
        $priorityClass = 'bg-orange-400 text-gray-800';
    } else {
        $priority = 'low';
        $priorityLabel = 'Low';
        $priorityClass = 'bg-green-500 text-white';
    }

    return array_merge($order, [
        'days_remaining' => $daysDiff,
        'priority' => $priority,
        'priority_label' => $priorityLabel,
        'priority_class' => $priorityClass,
        'due_date_obj' => $dueDate
    ]);
})->sortByDesc(function ($p) {

    return match($p['priority']) {
        'overdue' => 5,
        'high' => 4,
        'medium' => 3,
        'low' => 2,
        'completed' => 1
    };
});
@endphp

<div class="p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-nexora-deep-navy">Project Schedule & Timeline</h2>
            <p class="text-sm text-nexora-slate-500 mt-1">View all projects, deadlines, and priority levels</p>
        </div>

        <!-- Priority Legend -->
        <div class="flex flex-wrap gap-3 text-xs">
            <span class="px-2.5 py-1 rounded bg-red-600 text-white">Overdue</span>
            <span class="px-2.5 py-1 rounded bg-red-500 text-white">High (≤3 days)</span>
            <span class="px-2.5 py-1 rounded bg-orange-400 text-gray-800">Medium (4–7 days)</span>
            <span class="px-2.5 py-1 rounded bg-green-500 text-white">Low (>7 days)</span>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="flex flex-wrap gap-3">
        <button onclick="filterSchedule('all', event)" class="filter-sched px-3 py-1.5 rounded bg-nexora-corporate text-white text-sm">All Projects</button>
        <button onclick="filterSchedule('overdue', event)" class="filter-sched px-3 py-1.5 rounded bg-gray-200 text-gray-700 text-sm hover:bg-gray-300">Overdue</button>
        <button onclick="filterSchedule('high', event)" class="filter-sched px-3 py-1.5 rounded bg-gray-200 text-gray-700 text-sm hover:bg-gray-300">High Priority</button>
        <button onclick="filterSchedule('medium', event)" class="filter-sched px-3 py-1.5 rounded bg-gray-200 text-gray-700 text-sm hover:bg-gray-300">Medium Priority</button>
        <button onclick="filterSchedule('low', event)" class="filter-sched px-3 py-1.5 rounded bg-gray-200 text-gray-700 text-sm hover:bg-gray-300">Low Priority</button>
    </div>

    <!-- Timeline View -->
    <div class="bg-white rounded-lg border border-nexora-corporate/30 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-nexora-slate-100 border-b border-nexora-corporate/30">
                    <tr>
                        <th class="text-left p-3 text-nexora-deep-navy font-semibold">Project ID</th>
                        <th class="text-left p-3 text-nexora-deep-navy font-semibold">Project Name</th>
                        <th class="text-center p-3 text-nexora-deep-navy font-semibold">Status</th>
                        <th class="text-center p-3 text-nexora-deep-navy font-semibold">Due Date</th>
                        <th class="text-center p-3 text-nexora-deep-navy font-semibold">Time Remaining</th>
                        <th class="text-center p-3 text-nexora-deep-navy font-semibold">Priority</th>
                        <th class="p-3 text-nexora-deep-navy font-semibold">Timeline</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projectsWithPriority as $project)
                    @php
                        $statusStyle = $statusStyles[$project['status']] ?? ['pill' => 'bg-gray-400 text-white'];
                        $days = $project['days_remaining'];
                        $timelineWidth = max(5, min(100, 100 - ($days * 3))); // Scale bar width
                        $timelineColor = match($project['priority']) {
                            'overdue' => '#dc2626',
                            'high' => '#ef4444',
                            'medium' => '#f97316',
                            'low' => '#22c55e',
                            'completed' => '#1B6FC8'
                        };
                    @endphp
                    <tr class="schedule-row border-b border-nexora-corporate/20 hover:bg-nexora-slate-50 transition"
                        data-priority="{{ $project['priority'] }}">
                        <td class="p-3 font-mono text-xs text-nexora-slate-600">{{ $project['id'] }}</td>
                        <td class="p-3 font-medium text-nexora-deep-navy">{{ $project['name'] }}</td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusStyle['pill'] }}">
                                {{ $project['status'] }}
                            </span>
                        </td>
                        <td class="p-3 text-center text-nexora-deep-navy">{{ $project['due'] }}</td>
                        <td class="p-3 text-center font-medium">
                            @if($project['priority'] === 'completed')
                                <span class="text-nexora-corporate font-semibold">Completed</span>
                            @elseif($days < 0)
                                <span class="text-red-600 font-semibold">{{ abs($days) }} days overdue</span>
                            @elseif($days === 0)
                                <span class="text-orange-600 font-semibold">Due today</span>
                            @else
                                <span class="text-nexora-deep-navy">{{ $days }} day{{ $days !== 1 ? 's' : '' }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            <span class="px-2.5 py-1 rounded text-xs font-semibold {{ $project['priority_class'] }}">
                                {{ $project['priority_label'] }}
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-300"
                                     style="width: {{ $timelineWidth }}%; background-color: {{ $timelineColor }};"></div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-nexora-slate-500 italic">No projects found in schedule</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed Triage Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h4 class="font-semibold text-red-800 mb-2">Overdue</h4>
            <p class="text-2xl font-bold text-red-700">{{ $projectsWithPriority->where('priority', 'overdue')->count() }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h4 class="font-semibold text-red-800 mb-2">High Priority</h4>
            <p class="text-2xl font-bold text-red-700">{{ $projectsWithPriority->where('priority', 'high')->count() }}</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <h4 class="font-semibold text-orange-800 mb-2">Medium Priority</h4>
            <p class="text-2xl font-bold text-orange-700">{{ $projectsWithPriority->where('priority', 'medium')->count() }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="font-semibold text-green-800 mb-2">Low Priority</h4>
            <p class="text-2xl font-bold text-green-700">{{ $projectsWithPriority->where('priority', 'low')->count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-nexora-slate-100 border border-nexora-corporate/30 rounded-lg p-4">
            <h4 class="font-semibold text-nexora-deep-navy mb-2">Completed</h4>
            <p class="text-2xl font-bold text-nexora-corporate">{{ $projectsWithPriority->where('priority', 'completed')->count() }}</p>
        </div>
    </div>

</div>
