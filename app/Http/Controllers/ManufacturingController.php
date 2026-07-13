<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ManufacturingController extends Controller
{
    private function readData(): array
    {
        return json_decode(file_get_contents(public_path('json/tempData.json')), true);
    }

    private function writeData(array $data): bool
    {
        return file_put_contents(
            public_path('json/tempData.json'),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }

    // ── Work Order: update parts + auto-finish + send to QC ─────────────────
    public function updateOrder(Request $request): JsonResponse
    {
        $orderIndex  = (int)   $request->input('orderIndex');
        $partChanges = (array) $request->input('partChanges', []);
        $sendToQC    = (bool)  $request->input('sendToQC', false);

        $tempData = $this->readData();

        if (!isset($tempData['workOrders'][$orderIndex])) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $order = &$tempData['workOrders'][$orderIndex];

        foreach ($partChanges as $partIdx => $newStatus) {
            $partIdx = (int) $partIdx;
            if (!isset($order['parts'][$partIdx])) continue;
            if ($order['parts'][$partIdx]['status'] === 'Sourcing' && $newStatus === 'Ready') {
                $order['parts'][$partIdx]['status'] = 'Ready';
            }
        }

        // Auto-finish: if all parts now Ready and status is Building
        $allReady = collect($order['parts'])->every(fn($p) => $p['status'] === 'Ready');
        if ($allReady && $order['status'] === 'Building') {
            $order['status'] = 'Finished';
        }

        if ($sendToQC && in_array($order['status'], ['Finished', 'Building'])) {
            $order['status'] = 'QC Check';
        }

        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    // ── QC Benchmark: save results + auto-create rework if flagged ───────────
    public function updateQC(Request $request): JsonResponse
    {
        $woId    = $request->input('woId');
        $results = $request->input('results', []);

        $tempData        = $this->readData();
        $sessions        = &$tempData['qcSessions'];
        $allowedVerdicts = ['Pass', 'Warn', 'Fail', ''];

        $cleanResults = array_map(fn($r) => [
            'checkId' => (string) ($r['checkId'] ?? ''),
            'value'   => isset($r['value']) && $r['value'] !== null ? (float) $r['value'] : null,
            'verdict' => in_array($r['verdict'] ?? '', $allowedVerdicts) ? ($r['verdict'] ?? '') : '',
            'note'    => (string) ($r['note'] ?? ''),
        ], $results);

        $found = false;
        foreach ($sessions as &$s) {
            if ($s['woId'] === $woId) { $s['results'] = $cleanResults; $found = true; break; }
        }
        if (!$found) {
            $sessions[] = ['woId' => $woId, 'template' => 'gaming', 'tech' => '', 'results' => $cleanResults];
        }

        // Auto-create rework order for any flagged results
        $flagged = array_values(array_filter($cleanResults, fn($r) => in_array($r['verdict'], ['Warn', 'Fail'])));
        if (count($flagged) > 0) {
            $existingRw = collect($tempData['reworkOrders'] ?? [])->firstWhere('woId', $woId);
            if (!$existingRw) {
                $wo          = collect($tempData['workOrders'])->firstWhere('id', $woId);
                $allCheckDefs = collect($tempData['qcTemplates'] ?? [])
                    ->flatMap(fn($checks) => $checks)->keyBy('id');

                $failedChecks = array_map(fn($r) => [
                    'checkId'   => $r['checkId'],
                    'checkName' => $allCheckDefs[$r['checkId']]['name'] ?? $r['checkId'],
                    'verdict'   => $r['verdict'],
                    'result'    => $r['value'] !== null
                        ? number_format($r['value']) . ' ' . ($allCheckDefs[$r['checkId']]['unit'] ?? '')
                        : '—',
                    'target'    => ($allCheckDefs[$r['checkId']]['operator'] ?? '') . ' '
                        . number_format($allCheckDefs[$r['checkId']]['target'] ?? 0) . ' '
                        . ($allCheckDefs[$r['checkId']]['unit'] ?? ''),
                    'reason'    => $r['note'] ?: 'Flagged during QC benchmark',
                ], $flagged);

                $rwCount = count($tempData['reworkOrders'] ?? []) + 1;
                $tempData['reworkOrders'][] = [
                    'id'                     => 'RW-2024-' . str_pad($rwCount, 3, '0', STR_PAD_LEFT),
                    'woId'                   => $woId,
                    'buildName'              => $wo['name'] ?? $woId,
                    'assignedTech'           => $wo['assigned'] ?? '',
                    'raisedBy'               => $wo['assigned'] ?? '',
                    'raisedDate'             => now()->format('M d, Y'),
                    'status'                 => 'In Rework',
                    'priority'               => 'Medium',
                    'failedChecks'           => $failedChecks,
                    'requiredParts'          => [],
                    'notes'                  => 'Auto-created from QC benchmark flags.',
                    'escalatedToProcurement' => false,
                ];
            }
        }

        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    // ── Rework: update status / priority / notes / escalate ──────────────────
    public function updateRework(Request $request): JsonResponse
    {
        $idx      = (int) $request->input('reworkIndex');
        $tempData = $this->readData();

        if (!isset($tempData['reworkOrders'][$idx])) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $rw = &$tempData['reworkOrders'][$idx];
        if ($request->has('status'))   $rw['status']   = $request->input('status');
        if ($request->has('priority')) $rw['priority'] = $request->input('priority');
        if ($request->has('notes'))    $rw['notes']    = $request->input('notes');
        if ($request->input('escalate')) $rw['escalatedToProcurement'] = true;

        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    // ── Rework: add replacement part ─────────────────────────────────────────
    public function addReworkPart(Request $request): JsonResponse
    {
        $idx      = (int) $request->input('reworkIndex');
        $part     = $request->input('part', []);
        $tempData = $this->readData();

        if (!isset($tempData['reworkOrders'][$idx])) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $tempData['reworkOrders'][$idx]['requiredParts'][] = [
            'name'   => (string) ($part['name']   ?? ''),
            'status' => (string) ($part['status'] ?? 'Sourcing'),
            'eta'    => $part['eta'] ?? null,
        ];

        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    // ── Rework: update existing replacement part ──────────────────────────────
    public function updateReworkPart(Request $request): JsonResponse
    {
        $rwIdx    = (int) $request->input('reworkIndex');
        $partIdx  = (int) $request->input('partIndex');
        $part     = $request->input('part', []);
        $tempData = $this->readData();

        if (!isset($tempData['reworkOrders'][$rwIdx]['requiredParts'][$partIdx])) {
            return response()->json(['success' => false, 'message' => 'Part not found.'], 404);
        }

        $tempData['reworkOrders'][$rwIdx]['requiredParts'][$partIdx] = [
            'name'   => (string) ($part['name']   ?? ''),
            'status' => (string) ($part['status'] ?? 'Sourcing'),
            'eta'    => $part['eta'] ?? null,
        ];

        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    // ── Analytics: add note to a QC session ──────────────────────────────────
    public function addQcNote(Request $request): JsonResponse
    {
        $woId     = $request->input('woId');
        $note     = $request->input('note', '');
        $tempData = $this->readData();

        $found = false;
        foreach ($tempData['qcSessions'] as &$s) {
            if ($s['woId'] === $woId) {
                if (!isset($s['notes'])) $s['notes'] = [];
                $s['notes'][] = ['text' => $note, 'date' => now()->format('M d, Y')];
                $found = true;
                break;
            }
        }

        if (!$found) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    // ── Worker CRUD ───────────────────────────────────────────────────────────
    public function addWorker(Request $request): JsonResponse
    {
        $tempData = $this->readData();
        $tempData['workers'][] = [
            'id'    => $request->input('id', time()),
            'name'  => $request->input('name'),
            'role'  => $request->input('role'),
            'notes' => $request->input('notes', ''),
        ];
        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    public function updateWorker(Request $request): JsonResponse
    {
        $tempData = $this->readData();
        $found    = false;
        foreach ($tempData['workers'] as &$w) {
            if ((string) $w['id'] === (string) $request->input('id')) {
                $w['name']  = $request->input('name');
                $w['role']  = $request->input('role');
                $w['notes'] = $request->input('notes', '');
                $found = true; break;
            }
        }
        if (!$found) return response()->json(['success' => false, 'message' => 'Worker not found.'], 404);
        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    public function deleteWorker(Request $request): JsonResponse
    {
        $tempData = $this->readData();
        $before   = count($tempData['workers']);
        $tempData['workers'] = array_values(array_filter(
            $tempData['workers'],
            fn($w) => (string) $w['id'] !== (string) $request->input('id')
        ));
        if (count($tempData['workers']) === $before) {
            return response()->json(['success' => false, 'message' => 'Worker not found.'], 404);
        }
        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }

    public function assignWorker(Request $request): JsonResponse
    {
        $tempData = $this->readData();
        $found    = false;
        foreach ($tempData['workOrders'] as &$order) {
            if ($order['id'] === $request->input('orderId')) {
                $order['assigned'] = $request->input('workerName');
                $found = true; break;
            }
        }
        if (!$found) return response()->json(['success' => false, 'message' => 'Work order not found.'], 404);
        return $this->writeData($tempData)
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => 'Failed to write file.'], 500);
    }
}
