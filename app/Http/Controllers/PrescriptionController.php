<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\Visit;
use App\Models\LabRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionController extends Controller
{
    /**
     * Store a newly created prescription in storage with strict validation and AJAX support.
     */
    public function store(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'medicine_id' => 'required|exists:medicines,id',
            'quantity'    => 'required|integer|min:1|max:1000',
            'dosage'      => 'nullable|string|max:100',
            'frequency'   => 'nullable|string|max:255',
            'duration'    => 'nullable|string|max:100',
        ], [
            'medicine_id.required' => 'Please select a medicine from the list.',
            'medicine_id.exists'   => 'Selected medicine does not exist in inventory.',
            'quantity.required'    => 'Quantity is required.',
            'quantity.integer'     => 'Quantity must be a positive integer.',
            'quantity.min'         => 'Quantity must be at least 1.',
        ]);

        try {
            DB::beginTransaction();

            $dosage = !empty(trim($request->input('dosage', ''))) ? trim($request->input('dosage')) : 'As directed';
            $frequency = !empty(trim($request->input('frequency', ''))) ? trim($request->input('frequency')) : 'As needed';
            $duration = !empty(trim($request->input('duration', ''))) ? trim($request->input('duration')) : 'Standard course';

            $visit->prescriptions()->create([
                'medicine_id' => $validated['medicine_id'],
                'quantity'    => $validated['quantity'] ?? 1,
                'dosage'      => $dosage,
                'frequency'   => $frequency,
                'duration'    => $duration,
                'status'      => 'pending',
            ]);

            // Auto-check visit completion status
            $visit->checkAndUpdateCompletionStatus();

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Medication prescribed successfully!',
                ]);
            }

            return redirect()->route('visits.show', $visit)
                             ->with('success', 'Medication prescribed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Prescription store failed for Visit #{$visit->id}: " . $e->getMessage());

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
     * Dispense a prescription using FEFO (First Expired, First Out) batch deduction logic.
     */
    public function dispense(Prescription $prescription)
    {
        if ($prescription->status === 'dispensed') {
            return redirect()->back()->with('error', 'This prescription has already been dispensed.');
        }

        $medicine = $prescription->medicine;

        if (!$medicine) {
            return redirect()->back()->with('error', 'Associated medicine record not found in inventory.');
        }

        $requestedQty = $prescription->quantity ?? 1;

        try {
            DB::beginTransaction();

            // 1. Fetch valid non-expired batches ordered by earliest expiry date (FEFO rule)
            $validBatches = $medicine->purchases()
                ->where('expiry_date', '>=', today())
                ->where('remaining_quantity', '>', 0)
                ->orderBy('expiry_date', 'asc')
                ->get();

            $totalValidStock = $validBatches->sum('remaining_quantity');

            // 2. Validate sufficient non-expired stock
            if ($totalValidStock < $requestedQty) {
                DB::rollBack();
                return redirect()->back()->with('error', "Insufficient valid stock for {$medicine->name}! Requested: {$requestedQty}, Available non-expired stock: {$totalValidStock}.");
            }

            // 3. FEFO Deduction Loop: Spill over deduction if closest batch quantity is exhausted
            $remainingToDeduct = $requestedQty;

            foreach ($validBatches as $batch) {
                if ($remainingToDeduct <= 0) {
                    break;
                }

                if ($batch->remaining_quantity >= $remainingToDeduct) {
                    $batch->quantity_sold += $remainingToDeduct;
                    $batch->remaining_quantity -= $remainingToDeduct;
                    $batch->save();
                    $remainingToDeduct = 0;
                } else {
                    $remainingToDeduct -= $batch->remaining_quantity;
                    $batch->quantity_sold += $batch->remaining_quantity;
                    $batch->remaining_quantity = 0;
                    $batch->save();
                }
            }

            // 4. Update prescription status
            $prescription->update(['status' => 'dispensed']);

            // 5. Clean, non-looping visit auto-completion check
            $visit = $prescription->visit;
            if ($visit) {
                $hasPendingPrescriptions = Prescription::where('visit_id', $visit->id)
                    ->where('status', '!=', 'dispensed')
                    ->exists();

                $hasPendingLab = LabRequest::where('visit_id', $visit->id)
                    ->where('status', '!=', 'completed')
                    ->exists();

                $hasDiagnosis = !empty(trim($visit->consultation?->final_dx ?? ''));

                if (!$hasPendingPrescriptions && !$hasPendingLab && $hasDiagnosis) {
                    $visit->update(['visit_status' => 'completed']);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', "Medication dispensed successfully via FEFO logic! ({$requestedQty} units deducted from earliest expiring batch)");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Prescription dispense failed for ID #{$prescription->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }
}