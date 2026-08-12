<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\LabRequest;
use App\Models\LabResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabResultController extends Controller
{
    /**
     * Check visit prerequisites before adding lab results.
     */
    public function checkPrerequisites($visitId)
    {
        $vital = \App\Models\VitalSign::where('visit_id', $visitId)->first();
        if (!$vital) {
            return ['status' => false, 'message' => 'Nurse Vital Signs must be recorded first!'];
        }

        $doctorNote = \App\Models\Consultation::where('visit_id', $visitId)->first();
        if (!$doctorNote) {
            return ['status' => false, 'message' => 'Doctor consultation note must be recorded first!'];
        }

        $labRequest = \App\Models\LabRequest::where('visit_id', $visitId)->first();
        if (!$labRequest) {
            return ['status' => false, 'message' => 'No laboratory test has been ordered for this visit!'];
        }

        return ['status' => true, 'message' => 'Prerequisites met.'];
    }

    /**
     * Store lab result, restricting entry unless lab request payment is marked 'paid'.
     */
    public function store(Request $request, Visit $visit)
    {
        // 1. Check basic visit workflow prerequisites
        $check = $this->checkPrerequisites($visit->id);
        if (!$check['status']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $check['message'],
                    'errors'  => ['result_value' => [$check['message']]],
                ], 422);
            }
            return redirect()->back()->with('error', $check['message']);
        }

        // Auto-populate test_name from selected lab request if omitted
        if (!empty($request->input('lab_request_id'))) {
            $labReq = LabRequest::find($request->input('lab_request_id'));
            if ($labReq && !empty($labReq->test_name)) {
                $request->merge(['test_name' => $labReq->test_name]);
            }
        } elseif (empty($request->input('test_name'))) {
            $firstLabReq = LabRequest::where('visit_id', $visit->id)->first();
            if ($firstLabReq) {
                $request->merge(['test_name' => $firstLabReq->test_name]);
            } else {
                $request->merge(['test_name' => 'General Lab Test']);
            }
        }

        $validated = $request->validate([
            'test_name'      => ['nullable', 'string', 'max:255'],
            'result_value'   => ['required', 'string', 'min:2', "regex:/^[a-zA-Z0-9\s.,()%<>\/:_-]+$/"],
            'remarks'        => ['nullable', 'string'],
            'lab_request_id' => ['nullable', 'exists:lab_requests,id'],
        ], [
            'result_value.required' => 'Result / Findings field cannot be empty.',
            'result_value.min'      => 'Result / Findings must be at least 2 characters.',
            'result_value.regex'    => 'Result / Findings must contain standard letters, numbers, and medical symbols.',
        ]);

        // 2. Lab Technician Restriction: Payment Guard Check
        if (!empty($validated['lab_request_id'])) {
            $labRequest = LabRequest::find($validated['lab_request_id']);
            if ($labRequest && $labRequest->payment_status !== 'paid') {
                $errMsg = "Cannot record lab result: Payment for '{$labRequest->test_name}' is pending (unpaid). Receptionist/Cashier must mark it as paid first.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errMsg,
                        'errors'  => ['lab_request_id' => [$errMsg]],
                    ], 422);
                }
                return redirect()->back()->with('error', $errMsg);
            }
        } else {
            // Verify at least one paid lab request exists for this visit
            $hasPaidRequest = LabRequest::where('visit_id', $visit->id)->where('payment_status', 'paid')->exists();
            if (!$hasPaidRequest) {
                $errMsg = 'Cannot record lab result: All laboratory requests for this visit have pending payment. Payment must be processed first.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errMsg,
                        'errors'  => ['result_value' => [$errMsg]],
                    ], 422);
                }
                return redirect()->back()->with('error', $errMsg);
            }
        }

        $testName = !empty($validated['test_name']) ? $validated['test_name'] : 'Laboratory Examination';

        try {
            DB::beginTransaction();

            $labResult = LabResult::create([
                'visit_id'       => $visit->id,
                'lab_request_id' => $validated['lab_request_id'] ?? null,
                'technician_id'  => auth()->id(),
                'test_name'      => $testName,
                'result_value'   => $validated['result_value'],
                'remarks'        => $validated['remarks'] ?? null,
            ]);

            // 3. Update associated LabRequest status to completed
            if (!empty($validated['lab_request_id'])) {
                $labRequest = LabRequest::find($validated['lab_request_id']);
                if ($labRequest) {
                    $labRequest->update([
                        'status'      => 'completed',
                        'lab_tech_id' => auth()->id(),
                        'result'      => [
                            'value'   => $validated['result_value'],
                            'remarks' => $validated['remarks'] ?? '',
                        ],
                    ]);
                }
            }

            // Auto-check visit completion status
            $visit->checkAndUpdateCompletionStatus();

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                $labResult->load('technician');
                return response()->json([
                    'success' => true,
                    'message' => 'Lab test result saved successfully!',
                    'data'    => [
                        'id'              => $labResult->id,
                        'lab_request_id'  => $labResult->lab_request_id,
                        'test_name'       => $labResult->test_name,
                        'result_value'    => $labResult->result_value,
                        'remarks'         => $labResult->remarks,
                        'technician_name' => $labResult->technician->name ?? auth()->user()->name ?? 'Lab Technician',
                        'created_at'      => $labResult->created_at ? $labResult->created_at->format('M d, Y h:i A') : now()->format('M d, Y h:i A'),
                        'status'          => 'Completed',
                    ]
                ]);
            }

            return redirect()->back()->with('success', 'Laboratory result recorded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Lab result store failed for Visit #{$visit->id}: " . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operation failed: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }
}