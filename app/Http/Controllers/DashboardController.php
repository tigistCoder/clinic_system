<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\Medicine;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard view with live database metrics.
     */
    public function index()
    {
        $todayPatientsCount = Patient::whereDate('created_at', today())->count();
        $totalPatientsCount = Patient::count();
        $totalStaffCount = User::count();
        $labRequestsCount = LabRequest::count();
        $todayLabRequestsCount = LabRequest::whereDate('created_at', today())->count();
        $pendingLabRequestsCount = LabRequest::whereIn('status', ['pending', 'pending_payment', 'paid_pending_test'])->whereDate('created_at', today())->count();
        $completedLabRequestsCount = LabRequest::where('status', 'completed')->whereDate('updated_at', today())->count();
        $todayVisits = Visit::with('patient')->whereDate('created_at', today())->get();
        $pendingVitalsCount = Visit::whereDate('created_at', today())->doesntHave('vitalSign')->count();
        $pendingConsultationsCount = Visit::whereDate('created_at', today())->doesntHave('consultation')->count();
        $labResultsReadyCount = LabRequest::where('status', 'completed')->whereDate('updated_at', today())->count();
        $pendingLabPaymentsCount = LabRequest::whereIn('payment_status', ['unpaid', 'pending'])->whereDate('created_at', today())->count();
        $pendingPrescriptionsCount = Prescription::where('status', 'pending')->whereDate('created_at', today())->count();
        $dispensedPrescriptionsCount = Prescription::where('status', 'dispensed')->whereDate('updated_at', today())->count();
        $lowStockMedicinesCount = Medicine::with('purchases')->get()->filter(fn($m) => $m->valid_stock_quantity <= 10)->count();

        return view('dashboard', compact(
            'todayPatientsCount',
            'totalPatientsCount',
            'totalStaffCount',
            'labRequestsCount',
            'todayLabRequestsCount',
            'pendingLabRequestsCount',
            'completedLabRequestsCount',
            'todayVisits',
            'pendingVitalsCount',
            'pendingConsultationsCount',
            'labResultsReadyCount',
            'pendingLabPaymentsCount',
            'pendingPrescriptionsCount',
            'dispensedPrescriptionsCount',
            'lowStockMedicinesCount'
        ));
    }
}
