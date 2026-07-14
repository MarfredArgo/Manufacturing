<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Worker;
use App\Models\QcTemplate;
use App\Models\QcSession;
use App\Models\ReworkOrder;
use App\Services\ManufacturingDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ManufacturingController extends Controller
{
    // ── Main page ─────────────────────────────────────────────────────────────
    public function index()
    {
        $data = (new ManufacturingDataService())->loadAll();

        return view('Manufacturing', [
            'workOrders'   => $data['workOrders'],
            'workers'      => $data['workers'],
            'qcTemplates'  => $data['qcTemplates'],
            'qcSessions'   => $data['qcSessions'],
            'reworkOrders' => $data['reworkOrders'],
            'statusStyles' => config('nexora.statusStyles'),
            'partStyles'   => config('nexora.partStyles'),
            'tempData'     => array_merge($data, [
                'statusStyles' => config('nexora.statusStyles'),
                'partStyles'   => config('nexora.partStyles'),
            ]),
        ]);
    }

    // ── Work Order: update parts + auto-finish + send to QC ─────────────────
    public function updateOrder(Request $request): JsonResponse
    {
        $orderIndex  = (int)  $request->input('orderIndex');
        $partChanges = (array) $request->input('partChanges', []);
        $sendToQC    = (bool)  $request->input('sendToQC', false);

        $order = WorkOrder::with('parts')->orderBy('id', 'desc')->get()->values()->get($orderIndex);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        DB::transaction(function () use ($order, $partChanges, $sendToQC) {
            $partsByPosition = $order->parts->values();

            foreach ($partChanges as $position => $newStatus) {
                $part = $partsByPosition->get((int) $position);
                if (!$part) continue;
                if ($part->status === 'Sourcing' && $newStatus === 'Ready') {
                    $part->update(['status' => 'Ready']);
                }
            }

            $order->refresh()->load('parts');

            $allReady = $order->parts->every(fn ($p) => $p->status === 'Ready');
            if ($allReady && $order->status === 'Building') {
                $order->status = 'Finished';
            }

            if ($sendToQC && in_array($order->status, ['Finished', 'Building'])) {
                $order->status = 'QC Check';
            }

            $order->save();
        });

        return response()->json(['success' => true]);
    }

    // ── QC Benchmark: save results + auto-create rework if flagged ───────────
    public function updateQC(Request $request): JsonResponse
    {
        $woId    = $request->input('woId');
        $results = $request->input('results', []);

        $allowedVerdicts = ['Pass', 'Warn', 'Fail', ''];
        $cleanResults = array_map(fn ($r) => [
            'checkId' => (string) ($r['checkId'] ?? ''),
            'value'   => isset($r['value']) && $r['value'] !== null ? (float) $r['value'] : null,
            'verdict' => in_array($r['verdict'] ?? '', $allowedVerdicts) ? ($r['verdict'] ?? '') : '',
            'note'    => (string) ($r['note'] ?? ''),
        ], $results);

        DB::transaction(function () use ($woId, $cleanResults) {
            $session = QcSession::where('wo_id', $woId)->first();
            if (!$session) {
                $session = QcSession::create(['wo_id' => $woId, 'build_type' => 'gaming', 'tech' => '']);
            }

            $session->results()->delete();
            foreach ($cleanResults as $r) {
                $session->results()->create([
                    'check_id' => $r['checkId'],
                    'value'    => $r['value'],
                    'verdict'  => $r['verdict'],
                    'note'     => $r['note'],
                ]);
            }

            // Auto-create rework order for any flagged results
            $flagged = array_values(array_filter($cleanResults, fn ($r) => in_array($r['verdict'], ['Warn', 'Fail'])));
            if (count($flagged) > 0 && !ReworkOrder::where('wo_id', $woId)->exists()) {
                $wo = WorkOrder::find($woId);
                $checkDefs = QcTemplate::whereIn('id', array_column($flagged, 'checkId'))->get()->keyBy('id');

                $rwCount  = ReworkOrder::count() + 1;
                $reworkId = 'RW-2024-' . str_pad((string) $rwCount, 3, '0', STR_PAD_LEFT);

                $rework = ReworkOrder::create([
                    'id'                       => $reworkId,
                    'wo_id'                    => $woId,
                    'build_name'               => $wo->name ?? $woId,
                    'assigned_tech'            => $wo->assigned ?? '',
                    'raised_by'                => $wo->assigned ?? '',
                    'raised_date'              => now()->format('M d, Y'),
                    'status'                   => 'In Rework',
                    'priority'                 => 'Medium',
                    'notes'                    => 'Auto-created from QC benchmark flags.',
                    'escalated_to_procurement' => false,
                ]);

                foreach ($flagged as $r) {
                    $def = $checkDefs[$r['checkId']] ?? null;
                    $rework->failedChecks()->create([
                        'check_id'   => $r['checkId'],
                        'check_name' => $def->name ?? $r['checkId'],
                        'verdict'    => $r['verdict'],
                        'result'     => $r['value'] !== null
                            ? number_format($r['value']) . ' ' . ($def->unit ?? '')
                            : '—',
                        'target'     => ($def->operator ?? '') . ' ' . number_format($def->target ?? 0) . ' ' . ($def->unit ?? ''),
                        'reason'     => $r['note'] ?: 'Flagged during QC benchmark',
                    ]);
                }
            }
        });

        return response()->json(['success' => true]);
    }

    // ── Rework: update status / priority / notes / escalate ──────────────────
    public function updateRework(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $rw = ReworkOrder::orderBy('id')->get()->values()->get($reworkIndex);

        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        if ($request->has('status'))     $rw->status   = $request->input('status');
        if ($request->has('priority'))   $rw->priority = $request->input('priority');
        if ($request->has('notes'))      $rw->notes    = $request->input('notes');
        if ($request->input('escalate')) $rw->escalated_to_procurement = true;
        $rw->save();

        return response()->json(['success' => true]);
    }

    // ── Rework: add replacement part ─────────────────────────────────────────
    public function addReworkPart(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $part        = $request->input('part', []);

        $rw = ReworkOrder::orderBy('id')->get()->values()->get($reworkIndex);
        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $rw->requiredParts()->create([
            'name'   => (string) ($part['name']   ?? ''),
            'status' => (string) ($part['status'] ?? 'Sourcing'),
            'eta'    => $part['eta'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Rework: update existing replacement part ──────────────────────────────
    public function updateReworkPart(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $partIndex   = (int) $request->input('partIndex');
        $part        = $request->input('part', []);

        $rw = ReworkOrder::with('requiredParts')->orderBy('id')->get()->values()->get($reworkIndex);
        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $rp = $rw->requiredParts->values()->get($partIndex);
        if (!$rp) {
            return response()->json(['success' => false, 'message' => 'Part not found.'], 404);
        }

        $rp->update([
            'name'   => (string) ($part['name']   ?? ''),
            'status' => (string) ($part['status'] ?? 'Sourcing'),
            'eta'    => $part['eta'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Analytics: add note to a QC session ──────────────────────────────────
    public function addQcNote(Request $request): JsonResponse
    {
        $woId = $request->input('woId');
        $note = $request->input('note', '');

        $session = QcSession::where('wo_id', $woId)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $session->results()->create([
            'check_id' => null,
            'value'    => null,
            'verdict'  => '',
            'note'     => $note,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Worker CRUD ───────────────────────────────────────────────────────────
    public function addWorker(Request $request): JsonResponse
    {
        Worker::create([
            'name'  => $request->input('name'),
            'role'  => $request->input('role'),
            'notes' => $request->input('notes', ''),
        ]);
        return response()->json(['success' => true]);
    }

    public function updateWorker(Request $request): JsonResponse
    {
        $worker = Worker::find($request->input('id'));
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found.'], 404);
        }
        $worker->update([
            'name'  => $request->input('name'),
            'role'  => $request->input('role'),
            'notes' => $request->input('notes', ''),
        ]);
        return response()->json(['success' => true]);
    }

    public function deleteWorker(Request $request): JsonResponse
    {
        $worker = Worker::find($request->input('id'));
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found.'], 404);
        }
        $worker->delete();
        return response()->json(['success' => true]);
    }

    public function assignWorker(Request $request): JsonResponse
    {
        $order = WorkOrder::find($request->input('orderId'));
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Work order not found.'], 404);
        }
        $order->update(['assigned' => $request->input('workerName')]);
        return response()->json(['success' => true]);
    }
}
