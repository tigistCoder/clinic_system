<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'urgency_status' => 'nullable|in:normal,emergency',
        ]);

        
        $visit = $patient->visits()->create([
            'urgency_status' => $validated['urgency_status'] ?? 'normal',
            'visit_status'   => 'in_progress',
        ]);

        return redirect()->route('visits.show', $visit)
            ->with('success', 'Visit created successfully.');
    }

    public function show(Visit $visit)
    {
        $visit->load(['patient', 'vitalSign', 'consultation', 'labRequests', 'labResults.technician', 'prescriptions.medicine']);
        $visit->checkAndUpdateCompletionStatus();

        $medicines = Medicine::with('purchases')->orderBy('name')->get();
        return view('visits.show', compact('visit', 'medicines'));
    }
}