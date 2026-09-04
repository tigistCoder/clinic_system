<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\LabRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    /**
     * Display the Medical Records overview page with high-performance indexed summary metrics and clinical visit records.
     */
    public function index(Request $request)
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        // High-performance indexed range count queries
        $stats = [
            'total_patients'         => Patient::where('created_at', '>=', $monthStart)->where('created_at', '<=', $monthEnd)->count(),
            'total_visits'           => Visit::where('created_at', '>=', $monthStart)->where('created_at', '<=', $monthEnd)->count(),
            'pending_lab_requests'   => LabRequest::where('status', 'pending')->count(),
            'completed_lab_requests' => LabRequest::where('status', 'completed')->count(),
            'total_lab_tests'        => LabRequest::count(),
        ];

        // Clinical Visit Records Query (lightweight eager load for table list)
        $query = Visit::with('patient');

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
