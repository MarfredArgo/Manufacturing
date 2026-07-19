<?php

namespace App\Services;

use App\Models\WorkOrder;
use Carbon\Carbon;

class DueDateService
{
    private const DEFAULT_DAYS       = 7;
    private const EXTRA_DAYS         = 3;
    private const CAPACITY_THRESHOLD = 70;

    /**
     * Calculate the due date for an incoming work order.
     *
     * Default is order date + 7 days. If 70 or more orders already have a
     * due date falling within that same 7-day window, the queue is
     * considered congested and 3 extra days are added (10 days total).
     */
    public function calculate(?Carbon $orderDate = null): Carbon
    {
        $orderDate = ($orderDate ?? now())->copy();

        $windowStart = $orderDate->copy()->startOfDay();
        $windowEnd   = $orderDate->copy()->addDays(self::DEFAULT_DAYS)->endOfDay();

        $ordersInLine = WorkOrder::whereNotNull('due_date')
            ->whereBetween('due_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->count();

        $days = $ordersInLine >= self::CAPACITY_THRESHOLD
            ? self::DEFAULT_DAYS + self::EXTRA_DAYS
            : self::DEFAULT_DAYS;

        return $orderDate->copy()->addDays($days);
    }
}
