<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\VitalSign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VitalSignController extends Controller
{
    /**
     * የነርስ መመዝገቢያ ፎርም ማሳያ
     */
    public function create(Visit $visit)
    {
        $visit->load('patient');
        return view('vitals.create', compact('visit'));
    }

    /**
     * ቫይታል ሳይንስን ዳታቤዝ ላይ ማስቀመጫ
     */
    public function store(Request $request, Visit $visit)
{
    $validated = $request->validate([
        'systolic_bp'      => 'nullable|integer|min:50|max:250',
        'diastolic_bp'     => 'nullable|integer|min:30|max:150',
        'pulse_rate'       => 'nullable|integer|min:30|max:220',
        'respiratory_rate' => 'nullable|integer|min:8|max:60',
        'temperature'      => 'nullable|numeric|between:30.0,45.0',
        'spo2'             => 'nullable|integer|min:50|max:100',
        'weight'           => 'nullable|numeric|between:1,300',
        'height'           => 'nullable|numeric|between:20,250',
    ]);

    $visit->vitalSign()->updateOrCreate(
        ['visit_id' => $visit->id],
        array_merge($validated, ['nurse_id' => Auth::id()])
    );

    // Save እንደተደረገ ቀጥታ ወደ የቪዚት ዲቴይል ገጹ ይመለሳል
    return redirect()->route('patients.show', $visit->patient_id)
                     ->with('success', 'Vital signs saved successfully!');
}}