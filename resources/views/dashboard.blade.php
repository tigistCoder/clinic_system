@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">እንኳን ደህና መጡ፣ {{ auth()->user()->name }}! 👋</h2>
            <p class="text-slate-500 text-sm mt-1">የክሊኒኩ አጠቃላይ መረጃዎች እና የስራ እንቅስቃሴዎች እዚህ ይገኛሉ።</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                System Live
            </span>
        </div>
    </div>

    <!-- 3 Dynamic Count Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1: Total Patients -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Patients</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ number_format($total_patients ?? 0) }}</h3>
                <p class="text-xs text-blue-600 font-medium mt-1">Registered in system</p>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Card 2: Total Staff -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Staff</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ number_format($total_staff ?? 0) }}</h3>
                <p class="text-xs text-amber-600 font-medium mt-1">Active staff members</p>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="user-cog" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Card 3: Today's Visits -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Visits</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ number_format($today_visits ?? 0) }}</h3>
                <p class="text-xs text-emerald-600 font-medium mt-1">Visits recorded today</p>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
        </div>
    </div>
</div>
@endsection