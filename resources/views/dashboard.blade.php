@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-2xl font-bold text-slate-800">እንኳን ደህና መጡ፣ {{ auth()->user()->name }}! 👋</h2>
        <p class="text-slate-500 text-sm mt-1">የክሊኒኩ አጠቃላይ መረጃዎች እና የስራ እንቅስቃሴዎች እዚህ ይገኛሉ።</p>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @if(auth()->user()?->hasRole(['lab', 'lab_tech']))
        <!-- 1. የሚጠበቁ የላብ ምርመራዎች (Pending Lab Requests) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የሚጠበቁ የላብ ምርመራዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $pendingLabRequestsCount }}</h3>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. የተጠናቀቁ የላብ ምርመራዎች (Completed Lab Tests) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የተጠናቀቁ የላብ ምርመራዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $completedLabRequestsCount }}</h3>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. አጠቃላይ የላብራቶሪ ምርመራዎች (Total Lab Requests) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">አጠቃላይ የላብራቶሪ ምርመራዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $todayLabRequestsCount }}</h3>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="flask-conical" class="w-6 h-6"></i>
            </div>
        </div>
        @elseif(auth()->user()?->hasRole('nurse'))
        <!-- 1. የዛሬ ጉብኝቶች (Today's Visits) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የዛሬ ጉብኝቶች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $todayVisits->count() }}</h3>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. የሚጠበቁ ቫይታሎች (Pending Vitals) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የሚጠበቁ ቫይታሎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $pendingVitalsCount }}</h3>
            </div>
            <div class="p-3 bg-rose-50 text-rose-600 rounded-xl">
                <i data-lucide="activity" class="w-6 h-6"></i>
            </div>
        </div>
        @elseif(auth()->user()?->hasRole('doctor'))
        <!-- 1. Today's Patients -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Patients</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $todayVisits->count() }}</h3>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. Today's Pending Consultations -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Pending Consultations</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $pendingConsultationsCount }}</h3>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="stethoscope" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. Today's Lab Results Ready -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Lab Results Ready</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $labResultsReadyCount }}</h3>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="flask-conical" class="w-6 h-6"></i>
            </div>
        </div>
        @elseif(auth()->user()?->hasRole(['reception', 'receptionist']))
        <!-- 1. Today's Visits -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Visits</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $todayVisits->count() }}</h3>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. Total Patients -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Patients</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $totalPatientsCount }}</h3>
            </div>
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. Pending Lab Payments -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Lab Payments</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $pendingLabPaymentsCount }}</h3>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="credit-card" class="w-6 h-6"></i>
            </div>
        </div>
        @elseif(auth()->user()?->hasRole(['pharmacy', 'pharmacist']))
        <!-- 1. Today's Pending Prescriptions -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Pending Prescriptions</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $pendingPrescriptionsCount }}</h3>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="pill" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. Today's Dispensed Prescriptions -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Dispensed Prescriptions</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $dispensedPrescriptionsCount }}</h3>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="check-circle-2" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. Low Stock Medicines -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Low Stock Medicines</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $lowStockMedicinesCount }}</h3>
            </div>
            <div class="p-3 bg-rose-50 text-rose-600 rounded-xl">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
        </div>
        @else
        <!-- 1. የዛሬ ታካሚዎች -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የዛሬ ታካሚዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $todayPatientsCount }}</h3>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. አጠቃላይ ሰራተኞች -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">አጠቃላይ ሰራተኞች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $totalStaffCount }}</h3>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="user-cog" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. የላብራቶሪ ምርመራዎች -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የላብራቶሪ ምርመራዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $labRequestsCount }}</h3>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="flask-conical" class="w-6 h-6"></i>
            </div>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden p-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Today's Visits</h3>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                <th class="p-3">#</th>
                <th class="p-3">Today Visits (Patient Name)</th>
                <th class="p-3">Card Number</th>
                <th class="p-3">Age/Sex</th>
                <th class="p-3">Phone</th>
                <th class="p-3">Date / Time</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($todayVisits as $index => $visit)
                <tr class="hover:bg-gray-50/50" {{$visit->type =="normal" ? "bg-blue-50" : "bg-red-50"}}>
                    <td class="p-3 font-medium text-gray-500">{{ $index + 1 }}</td>
                    <td class="p-3 font-semibold text-gray-800">{{ $visit->patient->full_name }}</td>
                    <td class="p-3 text-blue-600 font-medium">#{{ $visit->patient->card_number }}</td> 
                    <td class="p-3 text-gray-600">{{ $visit->patient->age }} / {{ ucfirst($visit->patient->gender) }}</td>
                    <td class="p-3 text-gray-600">{{ $visit->patient->phone_number ?? '—' }}</td>
                    <td class="p-3 text-gray-500">{{ $visit->created_at->format('h:i A') }}</td>
                    <td class="p-3 text-gray-600">
                        <a href="{{ route('visits.show', $visit->id) }}"
                           class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition">
                            View Visit
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-400">No visits recorded today yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection