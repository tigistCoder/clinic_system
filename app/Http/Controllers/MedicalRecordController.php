<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\Prescription;
use App\Models\LabRequest;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    /**
     * Display the Medical Records overview page with top summary metrics and clinical visit records.
     */
    public function index(Request $request)
    {
        // Top 4 Metrics (Calculated for current month)
        $stats = [
            'total_patients'     => Patient::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count(),
            'total_visits'       => Visit::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count(),
            'total_monthly_sold' => Prescription::where('status', 'dispensed')
                                        ->whereMonth('updated_at', now()->month)
                                        ->whereYear('updated_at', now()->year)
                                        ->sum('quantity'),
            'total_lab_tests'    => LabRequest::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count(),
        ];

        // Clinical Visit Records Query
        $query = Visit::with(['patient', 'consultation', 'vitalSign', 'labRequests', 'labResults', 'prescriptions']);

        // Search Filter (by patient name, phone number, or card number/ID)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            })->orWhere('id', 'like', "%{$search}%");
        }

        $visits = $query->latest()->paginate(10)->withQueryString();

        return view('medical-records.index', compact('stats', 'visits'));
    }
}
