<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use App\Models\LabRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard view with live database metrics.
     */
    public function index()
    {
        $todayPatientsCount = Patient::whereDate('created_at', today())->count();
        $totalStaffCount = User::count();
        $labRequestsCount = LabRequest::count();
        $todayVisits=Visit::whereDate('created_at', today())->get();
        

        return view('dashboard', compact('todayPatientsCount', 'totalStaffCount', 'labRequestsCount','todayVisits'));
    }
}
