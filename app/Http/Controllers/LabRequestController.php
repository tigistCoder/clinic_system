<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use Illuminate\Http\Request;

class LabRequestController extends Controller
{
    public function store(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'test_name' => 'required|string',
            'price'     => 'required|numeric|min:0',
        ]);

        $visit->labRequests()->create([
            'test_name'      => $validated['test_name'],
            'price'          => $validated['price'],
            'payment_status' => 'unpaid',            // በ Migration ህግ መሰረት ('unpaid' ወይም 'paid')
            'status'         => 'pending_payment',   // በ Migration ህግ መሰረት ('pending_payment', 'paid_pending_test', 'completed')
        ]);

        return redirect()->route('patients.show', $visit->patient_id)
                         ->with('success', 'Lab test ordered successfully!');
    }
}