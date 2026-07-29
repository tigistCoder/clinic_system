<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultationController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'chief_complaint'         => 'required|string',
            'history_present_illness' => 'nullable|string',
            'physical_exam'           => 'nullable|string',
            'provisional_dx'          => 'required|string',
            'final_dx'                => 'nullable|string',
        ]);

        $visit->consultation()->updateOrCreate(
            ['visit_id' => $visit->id],
            [
                'doctor_id'               => Auth::id(),
                'chief_complaint'         => $request->input('chief_complaint'),
                'history_present_illness' => $request->input('history_present_illness'),
                'physical_exam'           => $request->input('physical_exam'),
                'provisional_dx'          => $request->input('provisional_dx'),
                'final_dx'                => $request->input('final_dx'),
            ]
        );

        return redirect()->route('patients.show', $visit->patient_id)
                         ->with('success', 'Consultation record saved successfully!');
    }
}