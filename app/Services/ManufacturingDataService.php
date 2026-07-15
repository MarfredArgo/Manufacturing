<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\Worker;
use App\Models\QcTemplate;
use App\Models\QcSession;
use App\Models\ReworkOrder;

class ManufacturingDataService
{
    /**
     * Loads everything the Manufacturing views need, shaped exactly like the
     * old tempData.json (minus statusStyles/partStyles — those are UI config
     * now, see config/nexora.php). Keeping the shape identical means every
     * blade partial keeps working without changes.
     */
    public function loadAll(): array
    {
        return [
            'workOrders'   => $this->workOrders(),
            'workers'      => $this->workers(),
            'qcTemplates'  => $this->qcTemplates(),
            'qcSessions'   => $this->qcSessions(),
            'reworkOrders' => $this->reworkOrders(),
        ];
    }

    public function workOrders(): array
    {
        return WorkOrder::with('parts')->orderBy('id', 'desc')->get()->map(fn ($wo) => [
            'id'       => $wo->id,
            'name'     => $wo->name,
            'specs'    => $wo->specs,
            'status'   => $wo->status,
            'due'      => $wo->due,
            'source'   => $wo->source,
            'assigned' => $wo->assigned,
            'parts'    => $wo->parts->map(fn ($p) => [
                'name'     => $p->name,
                'category' => $p->category,
                'status'   => $p->status,
            ])->values()->all(),
        ])->values()->all();
    }

    public function workers(): array
    {
        return Worker::orderBy('id')->get()->map(fn ($w) => [
            'id'    => $w->id,
            'name'  => $w->name,
            'role'  => $w->role,
            'notes' => $w->notes,
        ])->values()->all();
    }

    public function qcTemplates(): array
    {
        return QcTemplate::all()
            ->groupBy('build_type')
            ->map(fn ($checks) => $checks->map(fn ($c) => [
                'id'       => $c->id,
                'category' => $c->category,
                'name'     => $c->name,
                'tool'     => $c->tool,
                'target'   => is_numeric($c->target) ? $c->target + 0 : $c->target, // "20000.00" -> 20000
                'operator' => $c->operator,
                'unit'     => $c->unit,
            ])->values()->all())
            ->all();
    }

    public function qcSessions(): array
    {
        return QcSession::with('results')->get()->map(fn ($s) => [
            'woId'     => $s->wo_id,
            'template' => $s->build_type,
            'tech'     => $s->tech,
            'results'  => $s->results->map(fn ($r) => [
                'checkId' => $r->check_id,
                'value'   => $r->value !== null ? $r->value + 0 : null,
                'verdict' => $r->verdict,
                'note'    => $r->note,
            ])->values()->all(),
        ])->values()->all();
    }

    public function reworkOrders(): array
    {
        return ReworkOrder::with(['failedChecks', 'requiredParts'])->orderBy('id')->get()->map(fn ($rw) => [
            'id'                     => $rw->id,
            'woId'                   => $rw->wo_id,
            'buildName'              => $rw->build_name,
            'assignedTech'           => $rw->assigned_tech,
            'raisedBy'               => $rw->raised_by,
            'raisedDate'             => $rw->raised_date,
            'status'                 => $rw->status,
            'priority'               => $rw->priority,
            'notes'                  => $rw->notes,
            'escalatedToProcurement' => (bool) $rw->escalated_to_procurement,
            'failedChecks'           => $rw->failedChecks->map(fn ($fc) => [
                'checkId'   => $fc->check_id,
                'checkName' => $fc->check_name,
                'verdict'   => $fc->verdict,
                'result'    => $fc->result,
                'target'    => $fc->target,
                'reason'    => $fc->reason,
            ])->values()->all(),
            'requiredParts'          => $rw->requiredParts->map(fn ($rp) => [
                'name'   => $rp->name,
                'status' => $rp->status,
                'eta'    => $rp->eta,
            ])->values()->all(),
        ])->values()->all();
    }
}
