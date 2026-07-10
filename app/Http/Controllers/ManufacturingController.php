<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ManufacturingController extends Controller
{
    //Status Update
    public function updateOrder(Request $request): JsonResponse
    {
        $orderIndex  = (int)  $request->input('orderIndex');
        $partChanges = (array) $request->input('partChanges', []);  // { "2": "Ready" }
        $sendToQC    = (bool)  $request->input('sendToQC', false);
    
        $path     = public_path('json/tempData.json');
        $tempData = json_decode(file_get_contents($path), true);
    
        // Validate index exists
        if (!isset($tempData['workOrders'][$orderIndex])) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
    
        $order = &$tempData['workOrders'][$orderIndex];

        foreach ($partChanges as $partIdx => $newStatus) {
            $partIdx = (int) $partIdx;
    
            if (!isset($order['parts'][$partIdx])) continue;
    
            $currentStatus = $order['parts'][$partIdx]['status'];
    
            if ($currentStatus === 'Sourcing' && $newStatus === 'Ready') {
                $order['parts'][$partIdx]['status'] = 'Ready';
            }
        }
        
        $autoFinish = (bool) $request->input('autoFinish', false);

        if ($autoFinish && $order['status'] === 'Building') {
            $order['status'] = 'Finished';
        }

        if ($sendToQC && $order['status'] === 'Finished') {
            $order['status'] = 'QC Check';
        }

        $written = file_put_contents(
            $path,
            json_encode($tempData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    
        if ($written === false) {
            return response()->json(['success' => false, 'message' => 'Failed to write JSON file.'], 500);
        }
    
        return response()->json(['success' => true]);
    }
    //QC update
    public function updateQC(Request $request): JsonResponse
    {
        $woId    = $request->input('woId');
        $results = $request->input('results', []); // [{ checkId, value, verdict, note }]
    
        $path     = public_path('json/tempData.json');
        $tempData = json_decode(file_get_contents($path), true);
    
        // Find existing session for this WO or create a new one
        $sessions   = &$tempData['qcSessions'];
        $sessionIdx = null;
    
        foreach ($sessions as $i => $s) {
            if ($s['woId'] === $woId) {
                $sessionIdx = $i;
                break;
            }
        }
    
        $allowedVerdicts = ['Pass', 'Warn', 'Fail', ''];
    
        $cleanResults = array_map(function ($r) use ($allowedVerdicts) {
            return [
                'checkId' => (string) ($r['checkId'] ?? ''),
                'value'   => isset($r['value']) && $r['value'] !== null ? (float) $r['value'] : null,
                'verdict' => in_array($r['verdict'] ?? '', $allowedVerdicts) ? ($r['verdict'] ?? '') : '',
                'note'    => (string) ($r['note'] ?? ''),
            ];
        }, $results);
    
        if ($sessionIdx !== null) {
            $sessions[$sessionIdx]['results'] = $cleanResults;
        } else {
            $sessions[] = [
                'woId'    => $woId,
                'results' => $cleanResults,
            ];
        }
    
        $written = file_put_contents(
            $path,
            json_encode($tempData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    
        if ($written === false) {
            return response()->json(['success' => false, 'message' => 'Failed to write JSON file.'], 500);
        }
    
        return response()->json(['success' => true]);
    }
}
?>