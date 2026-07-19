<?php

namespace App\Console\Commands;

use App\Models\WorkOrder;
use App\Services\DueDateService;
use Illuminate\Console\Command;

class BackfillDueDates extends Command
{
    protected $signature   = 'workorders:backfill-due-dates';
    protected $description = 'Assign due_date to existing work orders using DueDateService, processed in creation order.';

    public function handle(DueDateService $dueDateService): int
    {
        $orders = WorkOrder::whereNull('due_date')->orderBy('created_at', 'asc')->get();

        if ($orders->isEmpty()) {
            $this->info('No work orders need a due_date.');
            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            $dueDate = $dueDateService->calculate($order->created_at);
            $order->update(['due_date' => $dueDate->toDateString()]);
            $this->line("{$order->id} -> due_date set to {$dueDate->toDateString()}");
        }

        $this->info("Backfilled due_date for {$orders->count()} work order(s).");
        return self::SUCCESS;
    }
}
