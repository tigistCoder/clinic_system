<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    /**
     * Generate a strictly unique card number by extracting the max numeric suffix from existing records.
     */
    public static function generateUniqueCardNumber(): string
    {
        $lastCard = Patient::whereNotNull('card_number')
            ->where('card_number', 'LIKE', 'CARD%')
            ->orderByRaw("CAST(SUBSTRING(card_number, 5) AS UNSIGNED) DESC")
            ->first();

        $maxNumber = 0;
        if ($lastCard && !empty($lastCard->card_number)) {
            $numPart = preg_replace('/[^0-9]/', '', $lastCard->card_number);
            $maxNumber = (int) $numPart;
        }

        do {
            $maxNumber++;
            $candidate = 'CARD' . str_pad($maxNumber, 6, '0', STR_PAD_LEFT);
        } while (Patient::where('card_number', $candidate)->exists());

        return $candidate;
    }

    public function index(Request $request)
    {
        $query = Patient::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('card_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->latest()->paginate(10);
        $totalPatients = Patient::count();
        $todayVisits = Visit::whereDate('created_at', now()->today())->count();

        return view('patients.index', compact('patients', 'totalPatients', 'todayVisits')); 
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'max:255'],
            'age'     => ['required', 'integer', 'min:0', 'max:120', 'digits_between:1,3'],
            'gender'  => ['required', 'in:male,female'],
            'phone'   => ['required', 'numeric', 'digits_between:9,11'],
            'address' => ['required', 'string', 'regex:/^[a-zA-Z\s,]+$/', 'min:2', 'max:255'],
        ], [
            'name.regex'    => 'Patient name must contain letters only.',
            'address.regex' => 'Address must contain letters only, numbers are not allowed.',
        ]);

        try {
            DB::beginTransaction();

            $card_number = self::generateUniqueCardNumber();
                
            $patient = Patient::create([
                'card_number'  => $card_number,
                'full_name'    => $validated['name'],
                'age'          => $validated['age'],
                'gender'       => $validated['gender'],
                'phone_number' => $validated['phone'],
                'address'      => $validated['address'],
            ]);

            DB::commit();

            return redirect()->route('patients.index')
                ->with('success', 'ታካሚው በስኬት ተመዝግቧል!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Patient store failed: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    public function show(Patient $patient)
    {
        $patient->load([
            'visits.vitalSign',
            'visits.consultation',
            'visits.labRequests',
            'visits.prescriptions.medicine'
        ]);

        return view('patients.detail', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'max:255'],
            'age'     => ['required', 'integer', 'min:0', 'max:120', 'digits_between:1,3'],
            'gender'  => ['required', 'in:male,female'],
            'phone'   => ['required', 'numeric', 'digits_between:9,15'],
            'address' => ['required', 'string', 'regex:/^[a-zA-Z\s,]+$/', 'min:2', 'max:255'],
        ], [
            'name.regex'    => 'Patient name must contain letters only.',
            'address.regex' => 'Address must contain letters only, numbers are not allowed.',
        ]);

        try {
            DB::beginTransaction();

            if (empty($patient->card_number)) {
                $patient->card_number = self::generateUniqueCardNumber();
            }

            $patient->update([
                'card_number'  => $patient->card_number,
                'full_name'    => $validated['name'],
                'age'          => $validated['age'],
                'gender'       => $validated['gender'],
                'phone_number' => $validated['phone'],
                'address'      => $validated['address'],
            ]);

            DB::commit();

            return redirect()->route('patients.index')->with('success', 'Patient updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Patient update failed for ID #{$patient->id}: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    public function destroy(Patient $patient)
    {
        try {
            DB::beginTransaction();

            $patient->delete();

            DB::commit();

            return redirect()->route('patients.index')->with('success', 'Patient deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Patient delete failed for ID #{$patient->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }
}