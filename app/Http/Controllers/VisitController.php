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
        if (auth()->user()?->hasRole(['nurse', 'doctor', 'lab', 'lab_tech', 'pharmacy', 'pharmacist'])) {
            abort(403, 'Starting visits is restricted for this role.');
        }

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

    public function show($id)
    {
        $visit = Visit::with([
            'patient',
            'vitalSign',
            'vitalSigns',
            'consultation',
            'labRequests',
            'labResults.technician',
            'prescriptions.medicine'
        ])->findOrFail($id);

        $doctors = \App\Models\User::where('role', 'doctor')
            ->withCount(['visits as active_queue_count' => fn($q) => $q->whereIn('visit_status', ['in_progress', 'doctor_queue', 'in_consultation'])])
            ->get(['id', 'name']);
        $medicines = Medicine::with('purchases')->orderBy('name')->get();

        return view('visits.show', compact('visit', 'medicines', 'doctors'));
    }
}