@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Clinical History & Records</p>
                <h1 class="text-2xl font-bold text-slate-900">Medical Records</h1>
                <p class="text-sm text-slate-500">Comprehensive overview of patient visits, diagnoses, and treatments.</p>
            </div>
        </div>
    </div>

    <!-- Top Grid (Stat Cards) -->
    @if(auth()->user()?->hasRole(['lab', 'lab_tech']))
    <!-- 3 Lab Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 1. የሚጠበቁ የላብ ምርመራዎች (Pending Lab Requests) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የሚጠበቁ የላብ ምርመራዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $stats['pending_lab_requests'] ?? 0 }}</h3>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. የተጠናቀቁ የላብ ምርመራዎች (Completed Lab Tests) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የተጠናቀቁ የላብ ምርመራዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $stats['completed_lab_requests'] ?? 0 }}</h3>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. አጠቃላይ የላብራቶሪ ምርመራዎች (Total Lab Requests) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">አጠቃላይ የላብራቶሪ ምርመራዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ $stats['total_lab_tests'] ?? 0 }}</h3>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="flask-conical" class="w-6 h-6"></i>
            </div>
        </div>
    </div>
    @else
    <div class="grid gap-4 md:grid-cols-4">
        <!-- Card 1: TOTAL PATIENTS -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Patients</p>
                <div class="rounded-xl bg-blue-50 p-2 text-blue-600">
                    <i data-lucide="users" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ number_format($stats['total_patients']) }}</h3>
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">This Month</span>
            </div>
        </div>

        <!-- Card 2: TOTAL VISITS -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Visits</p>
                <div class="rounded-xl bg-indigo-50 p-2 text-indigo-600">
                    <i data-lucide="calendar" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ number_format($stats['total_visits']) }}</h3>
                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">This Month</span>
            </div>
        </div>

        <!-- Card 3: TOTAL MONTHLY SOLD -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Monthly Sold</p>
                <div class="rounded-xl bg-emerald-50 p-2 text-emerald-600">
                    <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ number_format($stats['total_monthly_sold']) }}</h3>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Units Dispensed</span>
            </div>
        </div>

        <!-- Card 4: TOTAL LAB TESTS -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Lab Tests</p>
                <div class="rounded-xl bg-violet-50 p-2 text-violet-600">
                    <i data-lucide="flask-conical" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ number_format($stats['total_lab_tests']) }}</h3>
                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">Orders This Month</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Table Area -->
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <!-- Table Header: Title Left, Search Right -->
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Medical Records</h2>
                <p class="text-xs text-slate-500">List of recent clinical visits and patient encounters.</p>
            </div>

            <!-- Search Bar -->
            <form method="GET" action="{{ route('medical-records.index') }}" class="flex items-center gap-2">
                <div class="relative w-full sm:w-80">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search patient name, phone or card #..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-4 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
                <button type="submit" class="rounded-2xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900">Search</button>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm border-collapse">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">#</th>
                        <th class="px-4 py-3.5">PATIENT NAME</th>
                        <th class="px-4 py-3.5">CARD NUMBER</th>
                        <th class="px-4 py-3.5">VISIT DATE</th>
                        <th class="px-4 py-3.5">STATUS</th>
                        <th class="px-4 py-3.5 text-right">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($visits as $index => $visit)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-4 py-3.5 font-medium text-slate-400">
                                {{ $visits->firstItem() + $index }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 font-bold text-white text-xs shadow-sm">
                                        {{ strtoupper(substr($visit->patient->full_name ?? 'P', 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $visit->patient->full_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $visit->patient->age }} yrs • {{ ucfirst($visit->patient->gender) }} • {{ $visit->patient->phone_number }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 font-bold text-slate-800">
                                #CARD-{{ str_pad($visit->patient->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-medium text-slate-800">{{ $visit->created_at->format('M d, Y') }}</span>
                                <p class="text-xs text-slate-400">{{ $visit->created_at->format('h:i A') }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($visit->visit_status === 'completed')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                        Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                        In Progress
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('visits.show', $visit) }}" class="inline-flex items-center gap-1 rounded-xl bg-blue-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                No medical records found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">Showing {{ $visits->firstItem() ?? 0 }} to {{ $visits->lastItem() ?? 0 }} of {{ $visits->total() }} records</p>
            <div class="{{ $visits->hasPages() ? '' : 'hidden' }}">
                {{ $visits->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
