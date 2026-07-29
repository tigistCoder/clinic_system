<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Visit;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'medicine_name' => 'required|string|max:255',
            'dosage'        => 'nullable|string',
            'frequency'     => 'nullable|string',
            'duration'      => 'nullable|string',
        ]);

        $medicine = Medicine::firstOrCreate(
            ['name' => trim($validated['medicine_name'])],
            [
                'generic_name' => trim($validated['medicine_name']),
                'stock_quantity' => 0,
                'price_per_unit' => 0,
            ]
        );

        $visit->prescriptions()->create([
            'medicine_id' => $medicine->id,
            'dosage'      => $validated['dosage'] ?? null,
            'frequency'   => $validated['frequency'] ?? null,
            'duration'    => $validated['duration'] ?? null,
            'status'      => 'pending',
        ]);

        return redirect()->route('visits.show', $visit)
                         ->with('success', 'Medication prescribed successfully!');
    }
}