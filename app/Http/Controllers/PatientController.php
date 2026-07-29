<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\Request;
   use App\Models\Medicine; 

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->latest()->paginate(10);
        $totalPatients = Patient::count();
        
        // 💡 የዛሬ የነበሩትን Visits ብቻ በዳታቤዝ ፈልጎ ይቆጥራል
        $todayVisits = Visit::whereDate('created_at', now()->today())->count();

        return view('patients.index', compact('patients', 'totalPatients', 'todayVisits')); 
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:0',
            'gender' => 'required|in:male,female',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $patient = Patient::create([
            'full_name' => $validated['name'],
            'age' => $validated['age'],
            'gender' => $validated['gender'],
            'phone_number' => $validated['phone'],
            'address' => $validated['address'] ?? null,
        ]);

        return redirect()->route('patients.index')
            ->with('success', $patient ? 'ታካሚው በስኬት ተመዝግቧል!' : 'ታካሚውን መመዝገብ አልተሳካም።');
    }


public function show(Patient $patient)
{
    $patient->load([
        'visits.vitalSign',
        'visits.consultation',
        'visits.labRequests',
        'visits.prescriptions' // Prescriptions ከነ መድኃኒት ስማቸው እንዲመጡ
    ]);

    

    return view('patients.detail', compact('patient', ));
}

    public function edit(Patient $patient)
    {
        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:0',
            'gender' => 'required|in:male,female',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $patient->update([
            'full_name' => $validated['name'],
            'age' => $validated['age'],
            'gender' => $validated['gender'],
            'phone_number' => $validated['phone'],
            'address' => $validated['address'] ?? null,
        ]);

        return redirect()->route('patients.index')->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('patients.index')->with('success', 'Patient deleted successfully.');
    }
}