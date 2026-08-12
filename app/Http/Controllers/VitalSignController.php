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
            'systolic_bp'      => 'nullable|numeric|between:40,250',
            'diastolic_bp'     => 'nullable|numeric|between:20,180',
            'pulse_rate'       => 'nullable|numeric|between:30,250',
            'respiratory_rate' => 'nullable|numeric|between:8,60',
            'temperature'      => 'nullable|numeric|between:30,45',
            'spo2'             => 'nullable|numeric|between:50,100',
            'weight'           => 'nullable|numeric|min:0.5|max:635',
            'height'           => 'nullable|numeric|between:0.546,2.74',
        ], [
            'systolic_bp.between'      => 'Systolic BP must be between 40 and 250 mmHg.',
            'systolic_bp.numeric'      => 'Systolic BP must be between 40 and 250 mmHg.',
            'diastolic_bp.between'     => 'Diastolic BP must be between 20 and 180 mmHg.',
            'diastolic_bp.numeric'     => 'Diastolic BP must be between 20 and 180 mmHg.',
            'pulse_rate.between'       => 'Pulse rate must be between 30 and 250 bpm.',
            'pulse_rate.numeric'       => 'Pulse rate must be between 30 and 250 bpm.',
            'respiratory_rate.between' => 'Respiratory rate must be between 8 and 60 bpm.',
            'respiratory_rate.numeric' => 'Respiratory rate must be between 8 and 60 bpm.',
            'temperature.between'      => 'Temperature must be between 30°C and 45°C.',
            'temperature.numeric'      => 'Temperature must be between 30°C and 45°C.',
            'spo2.between'             => 'SpO2 percentage must be between 50% and 100%.',
            'spo2.numeric'             => 'SpO2 percentage must be between 50% and 100%.',
            'weight.max'               => 'Weight cannot exceed 635 kg.',
            'weight.numeric'           => 'Weight cannot exceed 635 kg.',
        ]);

        try {
            DB::beginTransaction();

            $nurseId = Auth::id() ?? User::first()?->id ?? 1;

            $visit->vitalSign()->updateOrCreate(
                ['visit_id' => $visit->id],
                array_merge($validated, ['nurse_id' => $nurseId])
            );

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vital signs saved successfully!',
                ]);
            }

            return redirect()->route('visits.show', $visit)
                             ->with('success', 'Vital signs saved successfully!');
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