<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the 3 core dynamic metric cards for the dashboard.
     */
    public function index()
    {
        return view('dashboard', [
            'total_patients' => \App\Models\Patient::count(),
            'total_staff'    => \App\Models\User::count(),
            'today_visits'   => \App\Models\Visit::whereDate('created_at', today())->count(),
        ]);
    }
}