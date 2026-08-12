<?php

namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabRequestController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'test_ids'   => 'nullable|array|min:1',
            'test_name'  => ['required_without:test_ids', 'nullable', 'string', 'min:2', "regex:/^[a-zA-Z\s.,()-]+$/"],
            'price'      => 'nullable|numeric|min:0',
        ], [
            'test_name.required_without' => 'Test name must contain letters and standard characters only.',
            'test_name.required'         => 'Test name must contain letters and standard characters only.',
            'test_name.min'              => 'Test name must contain letters and standard characters only.',
            'test_name.regex'            => 'Test name must contain letters and standard characters only.',
            'test_ids.required_without'  => 'Test name must contain letters and standard characters only.',
            'test_ids.min'               => 'Test name must contain letters and standard characters only.',
        ]);

        try {
            DB::beginTransaction();

            $price = $validated['price'] ?? 150.00;

            if (!empty($validated['test_ids']) && is_array($validated['test_ids'])) {
                foreach ($validated['test_ids'] as $tName) {
                    $visit->labRequests()->create([
                        'test_name'      => trim($tName),
                        'price'          => $price,
                        'payment_status' => 'unpaid',
                        'status'         => 'pending_payment',
                    ]);
                }
            } else {
                $visit->labRequests()->create([
                    'test_name'      => trim($validated['test_name']),
                    'price'          => $price,
                    'payment_status' => 'unpaid',
                    'status'         => 'pending_payment',
                ]);
            }

            // Auto update completion status
            $visit->checkAndUpdateCompletionStatus();

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lab test ordered successfully!',
                ]);
            }

            return redirect()->route('visits.show', $visit)
                             ->with('success', 'Lab test ordered successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Lab request store failed for Visit #{$visit->id}: " . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operation failed: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark a laboratory request as paid by Receptionist/Cashier/Admin.
     */
    public function pay(LabRequest $labRequest)
    {
        if ($labRequest->payment_status === 'paid') {
            return redirect()->back()->with('error', 'This laboratory request is already marked as paid.');
        }

        try {
            DB::beginTransaction();

            $labRequest->update([
                'payment_status' => 'paid',
                'cashier_id'     => auth()->id(),
                'status'         => 'paid_pending_test',
            ]);

            DB::commit();

            return redirect()->back()->with('success', "Payment of ETB {$labRequest->price} for {$labRequest->test_name} recorded successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Lab payment update failed for Request #{$labRequest->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }
}