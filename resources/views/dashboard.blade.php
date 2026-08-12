@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-2xl font-bold text-slate-800">እንኳን ደህና መጡ፣ {{ auth()->user()->name }}! 👋</h2>
        <p class="text-slate-500 text-sm mt-1">የክሊኒኩ አጠቃላይ መረጃዎች እና የስራ እንቅስቃሴዎች እዚህ ይገኛሉ።</p>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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