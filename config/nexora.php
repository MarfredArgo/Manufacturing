<?php

// UI styling only — badge/pill color classes keyed by status/part-status.
// Not real data, so this stays as PHP config rather than a DB table.

return [
    'statusStyles' => [
        'Building'  => ['pill' => 'bg-nexora-warning/80 text-nexora-off-white', 'dot' => 'bg-nexora-warning'],
        'QC Check'  => ['pill' => 'bg-nexora-info/80 text-nexora-off-white',    'dot' => 'bg-nexora-info'],
        'Cancelled' => ['pill' => 'bg-nexora-gray/80 text-nexora-off-white',    'dot' => 'bg-nexora-gray'],
        'Pending'   => ['pill' => 'bg-nexora-danger/80 text-nexora-off-white',  'dot' => 'bg-nexora-danger'],
        'Finished'  => ['pill' => 'bg-nexora-success/80 text-nexora-off-white', 'dot' => 'bg-nexora-success'],
    ],

    'partStyles' => [
        'Ready'    => ['dot' => 'bg-nexora-success', 'text' => 'text-nexora-success'],
        'Sourcing' => ['dot' => 'bg-nexora-warning', 'text' => 'text-nexora-warning'],
        'Missing'  => ['dot' => 'bg-nexora-danger',  'text' => 'text-nexora-danger'],
    ],
];
