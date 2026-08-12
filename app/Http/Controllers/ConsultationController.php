<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsultationController extends Controller
{
    /**
     * Store initial Doctor Consultation note (Chief Complaint, History, Physical Exam, Provisional Diagnosis).
     * Enforces strict alphabet/text-only validation across all text fields and supports AJAX responses.
     */
    public function store(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'chief_complaint'         => ['required', 'string', 'min:3', 'max:2000', 'regex:/^[\pL\s.,\-\?]+$/u'],
            'provisional_dx'          => ['required', 'string', 'min:3', 'max:2000', 'regex:/^[\pL\s.,\-\?]+$/u'],
            'history_present_illness' => ['nullable', 'string', 'max:2000', 'regex:/^[\pL\s.,\-\?]+$/u'],
            'physical_exam'           => ['nullable', 'string', 'max:2000', 'regex:/^[\pL\s.,\-\?]+$/u'],
            'doctor_notes'            => ['nullable', 'string', 'max:2000'],

            // Optional nested prescriptions array
            'prescriptions'                => ['nullable', 'array'],
            'prescriptions.*.medicine_id'  => ['required_with:prescriptions', 'exists:medicines,id'],
            'prescriptions.*.dosage'       => ['required_with:prescriptions', 'string', 'max:100'],
            'prescriptions.*.duration'     => ['required_with:prescriptions', 'integer', 'min:1'],
            'prescriptions.*.instructions' => ['nullable', 'string', 'max:255'],
        ], [
            'chief_complaint.required' => 'Please enter the patient chief complaint.',
            'chief_complaint.min'      => 'Chief complaint must be at least 3 characters.',
            'chief_complaint.regex'    => 'Only letters and standard text are allowed.',
            'provisional_dx.required'  => 'Diagnosis field cannot be empty.',
            'provisional_dx.min'       => 'Diagnosis must be at least 3 characters.',
            'provisional_dx.regex'     => 'Only letters and standard text are allowed.',
            'history_present_illness.regex' => 'Only letters and standard text are allowed.',
            'physical_exam.regex'      => 'Only letters and standard text are allowed.',
        ]);

        try {
            DB::beginTransaction();

            $doctorId = Auth::id() ?? User::first()?->id ?? 1;

            $visit->consultation()->updateOrCreate(
                ['visit_id' => $visit->id],
                [
                    'doctor_id'               => $doctorId,
                    'chief_complaint'         => $validated['chief_complaint'],
                    'history_present_illness' => $validated['history_present_illness'] ?? null,
                    'physical_exam'           => $validated['physical_exam'] ?? null,
                    'provisional_dx'          => $validated['provisional_dx'],
                ]
            );

            // Save prescriptions inside the same DB transaction if provided
            if (!empty($validated['prescriptions'])) {
                foreach ($validated['prescriptions'] as $px) {
                    $visit->prescriptions()->create([
                        'medicine_id'  => $px['medicine_id'],
                        'quantity'     => 1,
                        'dosage'       => $px['dosage'],
                        'frequency'    => $px['instructions'] ?? 'As directed',
                        'duration'     => $px['duration'] . ' days',
                        'status'       => 'pending',
                    ]);
                }
            }

            $visit->checkAndUpdateCompletionStatus();

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Doctor consultation record saved successfully!',
                ]);
            }

            return redirect()->route('visits.show', $visit)
                             ->with('success', 'Doctor consultation record saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Consultation store failed for Visit #{$visit->id}: " . $e->getMessage());

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
     * Store Final Diagnosis after prerequisite check (Vitals, Doctor Note, Order Lab, Lab Results).
     * Enforces strict alphabet/text-only validation.
     */
    public function storeFinalDiagnosis(Request $request, Visit $visit)
    {
        // Prerequisites Check Guard
        $missing = [];
        if (!$visit->vitalSign) {
            $missing[] = 'Add Vitals (Nurse Vital Signs)';
        }
        if (!$visit->consultation) {
            $missing[] = 'Doctor Note (Consultation)';
        }
        if ($visit->labRequests->isEmpty()) {
            $missing[] = 'Order Lab (Laboratory Request)';
        }
        if ($visit->labResults->isEmpty()) {
            $missing[] = 'Lab Result (Recorded Test Results)';
        }

        if (!empty($missing)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot enter Final Diagnosis yet! Required missing steps: ' . implode(' • ', $missing),
                ], 422);
            }
            return redirect()->back()->with('error', 'Cannot enter Final Diagnosis yet! Required missing steps: ' . implode(' • ', $missing));
        }

        $validated = $request->validate([
            'final_dx' => ['required', 'string', 'min:3', 'max:2000', 'regex:/^[\pL\s.,\-\?]+$/u'],
        ], [
            'final_dx.required' => 'Diagnosis field cannot be empty.',
            'final_dx.min'      => 'Diagnosis must be at least 3 characters.',
            'final_dx.regex'    => 'Only letters and standard text are allowed.',
        ]);

        try {
            DB::beginTransaction();

            $visit->consultation->update([
                'final_dx' => $validated['final_dx'],
            ]);

            $isCompleted = $visit->checkAndUpdateCompletionStatus();

            DB::commit();

            $msg = $isCompleted 
                ? 'Final Diagnosis saved successfully and visit marked complete!'
                : 'Final Diagnosis saved successfully! (Visit will be marked complete once all lab tests & prescriptions are fulfilled)';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                ]);
            }

            return redirect()->route('visits.show', $visit)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Final diagnosis store failed for Visit #{$visit->id}: " . $e->getMessage());

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