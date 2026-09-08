<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Visit;
use App\Models\VitalSign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VitalSignController extends Controller
{
    /**
     * Show vital sign entry form.
     */
    public function create(Visit $visit)
    {
        $visit->load('patient');
        return view('vitals.create', compact('visit'));
    }

    /**
     * Store or update vital signs with strict validation, DB transaction, and AJAX support.
     */
    public function store(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'temperature'       => 'nullable|numeric|min:0',
            'systolic_bp'       => 'nullable|numeric|min:0',
            'diastolic_bp'      => 'nullable|numeric|min:0',
            'pulse_rate'        => 'nullable|numeric|min:0',
            'respiratory_rate'  => 'nullable|numeric|min:0',
            'weight'            => 'nullable|numeric|min:0',
            'height'            => 'nullable|numeric|min:0',
            'spo2'              => 'nullable|numeric|min:0|max:100',
            'oxygen_saturation' => 'nullable|numeric|min:0|max:100',
            'doctor_id'         => 'nullable|exists:users,id',
            'urgency_status'    => 'nullable|in:normal,emergency',
        ], [
            'temperature.numeric'       => 'Temperature must be a valid number.',
            'temperature.min'           => 'Temperature cannot be negative.',
            'systolic_bp.numeric'       => 'Systolic BP must be a valid number.',
            'systolic_bp.min'           => 'Systolic BP cannot be negative.',
            'diastolic_bp.numeric'      => 'Diastolic BP must be a valid number.',
            'diastolic_bp.min'          => 'Diastolic BP cannot be negative.',
            'pulse_rate.numeric'        => 'Pulse rate must be a valid number.',
            'pulse_rate.min'            => 'Pulse rate cannot be negative.',
            'respiratory_rate.numeric'  => 'Respiratory rate must be a valid number.',
            'respiratory_rate.min'      => 'Respiratory rate cannot be negative.',
            'weight.numeric'            => 'Weight must be a valid number.',
            'weight.min'                => 'Weight cannot be negative.',
            'height.numeric'            => 'Height must be a valid number.',
            'height.min'                => 'Height cannot be negative.',
            'spo2.numeric'              => 'SpO2 must be a valid percentage.',
            'spo2.min'                  => 'SpO2 cannot be negative.',
            'spo2.max'                  => 'SpO2 percentage cannot exceed 100%.',
            'oxygen_saturation.numeric' => 'Oxygen saturation must be a valid percentage.',
            'oxygen_saturation.min'     => 'Oxygen saturation cannot be negative.',
            'oxygen_saturation.max'     => 'Oxygen saturation percentage cannot exceed 100%.',
        ]);

        $doctorId = $validated['doctor_id'] ?? null;
        $urgencyStatus = $validated['urgency_status'] ?? null;
        unset($validated['doctor_id'], $validated['urgency_status']);

        if (isset($validated['oxygen_saturation']) && !isset($validated['spo2'])) {
            $validated['spo2'] = $validated['oxygen_saturation'];
        }
        unset($validated['oxygen_saturation']);

        try {
            DB::beginTransaction();

            $nurseId = Auth::id() ?? User::first()?->id ?? 1;

            $visit->vitalSign()->updateOrCreate(
                ['visit_id' => $visit->id],
                array_merge($validated, ['nurse_id' => $nurseId])
            );

            $visitUpdate = [
                'visit_status' => 'doctor_queue',
            ];
            if ($doctorId) {
                $visitUpdate['doctor_id'] = $doctorId;
            }
            if ($urgencyStatus) {
                $visitUpdate['urgency_status'] = $urgencyStatus;
            }
            $visit->update($visitUpdate);

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Assigned to doctor queue.',
                ]);
            }

            return redirect()->back()->with('success', 'Assigned to doctor queue.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Vital signs store failed for Visit #{$visit->id}: " . $e->getMessage());

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