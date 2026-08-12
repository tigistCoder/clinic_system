@extends('layouts.app')

@section('content')
@php
    $hasVitals = (bool) $visit->vitalSign;
    $hasDoctorNote = (bool) $visit->consultation;
    $hasLabOrder = $visit->labRequests->isNotEmpty();
    $hasLabResult = $visit->labResults->isNotEmpty();
    $prerequisitesMet = $hasVitals && $hasDoctorNote && $hasLabOrder && $hasLabResult;

    $missingSteps = [];
    if (!$hasVitals) $missingSteps[] = '1. Add Vitals (Nurse Vital Signs)';
    if (!$hasDoctorNote) $missingSteps[] = '2. Doctor Note (Consultation Findings)';
    if (!$hasLabOrder) $missingSteps[] = '3. Order Lab (Laboratory Request)';
    if (!$hasLabResult) $missingSteps[] = '4. Lab Result (Recorded Test Results)';

    $physicalExamText = old(
        'physical_exam',
        is_array($visit->consultation?->physical_exam ?? null)
            ? implode(', ', $visit->consultation->physical_exam)
            : ($visit->consultation?->physical_exam ?? '')
    );
@endphp

{{-- Modal transition styles --}}
<style>
    .modal-backdrop {
        position: fixed; inset: 0; z-index: 50;
        display: flex; align-items: center; justify-content: center;
        background: rgba(15, 23, 42, 0.5); padding: 1rem;
        opacity: 0; visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
    }
    .modal-backdrop.open { opacity: 1; visibility: visible; }
    .modal-backdrop .modal-panel {
        transform: scale(0.95); transition: transform 0.2s ease;
    }
    .modal-backdrop.open .modal-panel { transform: scale(1); }
    .field-error { display: none; }
</style>

<div class="space-y-6">

    {{-- ═══════════════════ Flash Messages ═══════════════════ --}}
    @if(session('success') || session('error') || (isset($errors) && $errors->any()))
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-1">
            @if(session('success'))
                <p class="text-sm font-semibold text-emerald-700">✓ {{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="text-sm font-semibold text-rose-700">✕ {{ session('error') }}</p>
            @endif
            @if(isset($errors) && $errors->any())
                @foreach($errors->all() as $error)
                    <p class="text-sm font-semibold text-rose-700">✕ {{ $error }}</p>
                @endforeach
            @endif
        </div>
    @endif

    {{-- ═══════════════════ Header Section ═══════════════════ --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-700">Visit #{{ $visit->id }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">{{ ucfirst($visit->urgency_status ?? 'normal') }}</span>
                    @if($visit->visit_status === 'completed')
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Completed</span>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-700">In Progress</span>
                    @endif
                </div>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $visit->patient->name ?? 'Patient File' }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Card: #{{ $visit->patient->card_number ?? 'CARD0001' }} • Gender: {{ ucfirst($visit->patient->gender ?? '-') }} • Age: {{ $visit->patient->age ?? '-' }} yrs • Started: {{ $visit->created_at->format('M d, Y h:i A') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('patients.show', $visit->patient) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    ← Patient Profile
                </a>
                <button type="button" onclick="openModal('vitalModal')" class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    + Add Vitals
                </button>
                <button type="button" onclick="openModal('doctorModal')" class="rounded-2xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    + Doctor Note
                </button>
                <button type="button" onclick="openModal('labModal')" class="rounded-2xl bg-violet-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-700">
                    + Order Lab
                </button>
                <button type="button" onclick="openModal('labResultModal')" class="rounded-2xl bg-teal-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-700">
                    + Enter Lab Result
                </button>
                <button type="button" onclick="openModal('prescriptionModal')" class="rounded-2xl bg-amber-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-700">
                    + Prescribe Medicine
                </button>

                @if($prerequisitesMet)
                    @if(in_array(auth()->user()->role, ['admin', 'doctor']))
                        <button type="button" onclick="openModal('finalDxModal')" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700">
                            ✓ Final Diagnosis
                        </button>
                    @endif
                @else
                    <button type="button" onclick="openModal('prerequisiteModal')" class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-2.5 text-xs font-bold text-rose-700 shadow-sm transition hover:bg-rose-100 flex items-center gap-1.5">
                        🔒 Final Diagnosis (Locked)
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════ Main Layout Grid ═══════════════════ --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Left Column (2 Cols) --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- ── Vitals Card ── --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Nurse Vital Signs</h2>
                    @if($hasVitals)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">✓ Recorded</span>
                    @else
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Pending</span>
                    @endif
                </div>

                @if($visit->vitalSign)
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-400">Blood Pressure</p>
                            <p class="mt-1 text-base font-bold text-slate-900">{{ $visit->vitalSign->systolic_bp ?? '-' }}/{{ $visit->vitalSign->diastolic_bp ?? '-' }} <span class="text-xs font-normal text-slate-500">mmHg</span></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-400">Pulse Rate</p>
                            <p class="mt-1 text-base font-bold text-slate-900">{{ $visit->vitalSign->pulse_rate ?? '-' }} <span class="text-xs font-normal text-slate-500">bpm</span></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-400">Temperature</p>
                            <p class="mt-1 text-base font-bold text-slate-900">{{ $visit->vitalSign->temperature ?? '-' }} <span class="text-xs font-normal text-slate-500">°C</span></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-400">SpO2</p>
                            <p class="mt-1 text-base font-bold text-slate-900">{{ $visit->vitalSign->spo2 ?? '-' }} <span class="text-xs font-normal text-slate-500">%</span></p>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                        No vital signs recorded yet. Click "+ Add Vitals" to record nurse readings.
                    </div>
                @endif
            </div>

            {{-- ── Doctor Consultation Notes Card ── --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-semibold text-slate-800">Doctor Consultation Findings</h2>
                    @if($hasDoctorNote)
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">✓ Notes Recorded</span>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Pending Doctor</span>
                    @endif
                </div>

                @if($visit->consultation)
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Chief Complaint</p>
                            <p class="mt-0.5 font-semibold text-slate-900">{{ $visit->consultation->chief_complaint }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">History of Present Illness</p>
                            <p class="mt-0.5 text-slate-700">{{ $visit->consultation->history_present_illness ?? 'None documented' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Physical Examination</p>
                            <p class="mt-0.5 text-slate-700">{{ is_array($visit->consultation->physical_exam) ? implode(', ', $visit->consultation->physical_exam) : ($visit->consultation->physical_exam ?? 'None documented') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Provisional Diagnosis</p>
                            <p class="mt-0.5 font-semibold text-blue-700">{{ $visit->consultation->provisional_dx }}</p>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                        No doctor consultation note recorded. Click "+ Doctor Note" to record findings.
                    </div>
                @endif
            </div>

            {{-- ── Laboratory Tests & Billing Card ── --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-semibold text-slate-800">Laboratory Tests & Billing</h2>
                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">{{ $visit->labRequests->count() }} Orders</span>
                </div>

                @if($visit->labRequests->isNotEmpty())
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-left text-sm border-collapse divide-y divide-slate-200">
                            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3">Test Name</th>
                                    <th class="px-4 py-3">Price</th>
                                    <th class="px-4 py-3">Payment Status</th>
                                    <th class="px-4 py-3">Result / Findings</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($visit->labRequests as $labReq)
                                    @php $res = $visit->labResults->firstWhere('lab_request_id', $labReq->id); @endphp
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $labReq->test_name }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-700 whitespace-nowrap">{{ number_format($labReq->price, 2) }} ETB</td>
                                        <td class="px-4 py-3">
                                            @if($labReq->payment_status === 'paid')
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">✓ Paid</span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">Pending</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($res)
                                                <div class="space-y-0.5">
                                                    <p class="font-bold text-slate-900 text-sm">{{ $res->result_value }}</p>
                                                    @if($res->remarks)
                                                        <p class="text-xs text-slate-500">Remarks: {{ $res->remarks }}</p>
                                                    @endif
                                                </div>
                                            @elseif($labReq->result)
                                                <p class="font-bold text-slate-900 text-sm">{{ is_array($labReq->result) ? ($labReq->result['value'] ?? '-') : $labReq->result }}</p>
                                            @else
                                                <span class="text-xs text-slate-400 font-medium">Awaiting result</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($labReq->status === 'completed' || $res)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">✓ Completed</span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">In Progress</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            @if($labReq->payment_status !== 'paid')
                                                @if(in_array(auth()->user()->role, ['admin', 'receptionist', 'cashier', 'doctor']))
                                                    <form action="{{ route('lab_requests.pay', $labReq) }}" method="POST" class="inline-block" onsubmit="return confirm('Mark payment of ETB {{ number_format($labReq->price, 2) }} as received for {{ $labReq->test_name }}?');">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                                            💳 Mark as Paid
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-slate-400">Requires Cashier</span>
                                                @endif
                                            @else
                                                <span class="text-xs font-semibold text-emerald-600">✓ Paid & Verified</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                        No laboratory tests ordered yet.
                    </div>
                @endif
            </div>

            {{-- ── Laboratory Results & Findings Card ── --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-semibold text-slate-800">Laboratory Results & Findings</h2>
                    <span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-700">{{ $visit->labResults->count() }} Results</span>
                </div>

                @if($visit->labResults->isNotEmpty())
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Test Name</th>
                                    <th class="p-3">Result / Findings</th>
                                    <th class="p-3">Tested By (Lab Tech)</th>
                                    <th class="p-3">Date & Time</th>
                                    <th class="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($visit->labResults as $labRes)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-3 font-semibold text-slate-900">{{ $labRes->test_name }}</td>
                                        <td class="p-3 font-medium text-slate-800">
                                            <p class="font-bold text-slate-900">{{ $labRes->result_value }}</p>
                                            @if($labRes->remarks)
                                                <p class="text-xs text-slate-500 mt-0.5">Remarks: {{ $labRes->remarks }}</p>
                                            @endif
                                        </td>
                                        <td class="p-3 text-slate-700 text-xs font-semibold">{{ $labRes->technician?->name ?? 'Lab Technician' }}</td>
                                        <td class="p-3 text-xs text-slate-500 whitespace-nowrap">{{ $labRes->created_at ? $labRes->created_at->format('M d, Y h:i A') : '-' }}</td>
                                        <td class="p-3">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">✓ Completed</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                        No laboratory results recorded yet. Click "+ Enter Lab Result" to record test findings.
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Column (1 Col) --}}
        <div class="space-y-6">

            {{-- ── Final Diagnosis Card ── --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-semibold text-slate-800">Confirmed Final Diagnosis</h2>
                    @if(!empty($visit->consultation?->final_dx))
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">✓ Confirmed</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Pending</span>
                    @endif
                </div>

                @if(!empty($visit->consultation?->final_dx))
                    <div class="rounded-2xl bg-indigo-50/60 border border-indigo-100 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-500">Final Clinical Diagnosis</p>
                        <p class="mt-1 text-base font-bold text-indigo-950">{{ $visit->consultation->final_dx }}</p>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                        No final diagnosis recorded yet.
                        @if($prerequisitesMet)
                            @if(in_array(auth()->user()->role, ['admin', 'doctor']))
                                <button type="button" onclick="openModal('finalDxModal')" class="ml-1 font-bold text-indigo-600 hover:underline">+ Record Final Diagnosis</button>
                            @endif
                        @else
                            <button type="button" onclick="openModal('prerequisiteModal')" class="ml-1 font-bold text-rose-600 hover:underline">View Missing Steps</button>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ── Prescriptions Card ── --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-amber-700 font-bold text-sm">💊</span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Prescriptions</h2>
                            <p class="text-xs text-slate-500">Medications & fulfillment status</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                            {{ $visit->prescriptions->count() }} {{ Str::plural('Item', $visit->prescriptions->count()) }}
                        </span>
                        <button type="button" onclick="openModal('prescriptionModal')" class="rounded-xl bg-amber-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-amber-700">
                            + Prescribe
                        </button>
                    </div>
                </div>

                @if($visit->prescriptions->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($visit->prescriptions as $prescription)
                            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 transition hover:bg-white hover:shadow-sm space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-sm">
                                            {{ $prescription->medicine->name ?? 'Medication Item' }}
                                        </h3>
                                        @if(!empty($prescription->medicine->generic_name))
                                            <p class="text-xs font-medium text-slate-400">
                                                Generic: {{ $prescription->medicine->generic_name }}
                                            </p>
                                        @endif
                                    </div>
                                    @if($prescription->status === 'dispensed')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            ✓ Dispensed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200/80 px-2.5 py-1 text-xs font-bold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            ⏳ Pending
                                        </span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/60">
                                    <div>
                                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Dosage & Regimen</span>
                                        <span class="font-semibold text-slate-700">{{ $prescription->dosage }}</span>
                                        <span class="text-slate-500 block text-[11px]">{{ $prescription->frequency }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Quantity & Duration</span>
                                        <span class="font-semibold text-slate-700">Qty: {{ $prescription->quantity }}</span>
                                        <span class="text-slate-500 block text-[11px]">{{ $prescription->duration }}</span>
                                    </div>
                                </div>

                                @if($prescription->status !== 'dispensed')
                                    @if(in_array(auth()->user()->role, ['admin', 'pharmacy', 'pharmacist']))
                                        <div class="pt-2 border-t border-slate-200/60 flex justify-end">
                                            <form action="{{ route('prescriptions.dispense', $prescription) }}" method="POST" class="inline-block" onsubmit="return confirm('Dispense {{ $prescription->quantity }} unit(s) of {{ $prescription->medicine->name ?? 'this medication' }}?');">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-amber-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-amber-700">
                                                    💊 Dispense Medication
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-6 text-center">
                        <p class="text-sm font-medium text-slate-500">No prescriptions recorded yet.</p>
                        <button type="button" onclick="openModal('prescriptionModal')" class="mt-2 text-xs font-bold text-amber-600 hover:underline">
                            + Prescribe Medication
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════════
         MODALS
    ═══════════════════════════════════════════════════════════════ --}}

    {{-- 1. Vital Signs Modal --}}
    <div id="vitalModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">+ Add Vital Signs</h3>
                <button type="button" onclick="closeModal('vitalModal')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form data-url="{{ route('vitals.store', $visit) }}" data-modal="vitalModal" onsubmit="submitModalForm(event)" class="space-y-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Systolic BP (mmHg)</label>
                        <input type="number" name="systolic_bp" value="{{ $visit->vitalSign?->systolic_bp ?? '' }}" placeholder="e.g. 120" min="40" max="250" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="systolic_bp"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Diastolic BP (mmHg)</label>
                        <input type="number" name="diastolic_bp" value="{{ $visit->vitalSign?->diastolic_bp ?? '' }}" placeholder="e.g. 80" min="20" max="180" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="diastolic_bp"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Pulse Rate (bpm)</label>
                        <input type="number" name="pulse_rate" value="{{ $visit->vitalSign?->pulse_rate ?? '' }}" placeholder="e.g. 72" min="30" max="250" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="pulse_rate"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Respiratory Rate (breaths/min)</label>
                        <input type="number" name="respiratory_rate" value="{{ $visit->vitalSign?->respiratory_rate ?? '' }}" placeholder="e.g. 16" min="8" max="60" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="respiratory_rate"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Temperature (°C)</label>
                        <input type="number" step="0.1" name="temperature" value="{{ $visit->vitalSign?->temperature ?? '' }}" placeholder="e.g. 36.6" min="30" max="45" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="temperature"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">SpO2 (%)</label>
                        <input type="number" name="spo2" value="{{ $visit->vitalSign?->spo2 ?? '' }}" placeholder="e.g. 98" min="50" max="100" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="spo2"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" value="{{ $visit->vitalSign?->weight ?? '' }}" placeholder="e.g. 70.5" min="0.5" max="635" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="weight"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Height (m/cm)</label>
                        <input type="number" step="0.01" name="height" value="{{ $visit->vitalSign?->height ?? '' }}" placeholder="e.g. 1.75" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="height"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('vitalModal')" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save Vitals</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Doctor Consultation Modal --}}
    <div id="doctorModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">+ Doctor Consultation Note</h3>
                <button type="button" onclick="closeModal('doctorModal')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form data-url="{{ route('consultations.store', $visit) }}" data-modal="doctorModal" onsubmit="submitModalForm(event)" class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Chief Complaint *</label>
                    <input type="text" name="chief_complaint" value="{{ old('chief_complaint', $visit->consultation?->chief_complaint ?? '') }}" placeholder="Chief complaint" minlength="3" maxlength="2000" pattern="^[\p{L}\s.,\-\?]+$" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="chief_complaint"></p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">History of Present Illness</label>
                    <textarea name="history_present_illness" rows="3" placeholder="History of present illness" pattern="^[\p{L}\s.,\-\?]+$" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">{{ old('history_present_illness', $visit->consultation?->history_present_illness ?? '') }}</textarea>
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="history_present_illness"></p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Physical Exam</label>
                    <textarea name="physical_exam" rows="3" placeholder="Physical exam findings" pattern="^[\p{L}\s.,\-\?]+$" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">{{ $physicalExamText }}</textarea>
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="physical_exam"></p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Provisional Diagnosis *</label>
                    <input type="text" name="provisional_dx" value="{{ old('provisional_dx', $visit->consultation?->provisional_dx ?? '') }}" placeholder="Provisional diagnosis" minlength="3" maxlength="2000" pattern="^[\p{L}\s.,\-\?]+$" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="provisional_dx"></p>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('doctorModal')" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Doctor Note</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Order Lab Test Modal --}}
    <div id="labModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">+ Order Laboratory Test</h3>
                <button type="button" onclick="closeModal('labModal')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form data-url="{{ route('lab_requests.store', $visit) }}" data-modal="labModal" onsubmit="submitModalForm(event)" class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Select / Enter Test Name *</label>
                    <input type="text" name="test_name" placeholder="e.g. Complete Blood Count (CBC), Urinalysis, Stool Test" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="test_name"></p>
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="test_ids"></p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Test Price (ETB) *</label>
                    <input type="number" step="0.01" name="price" value="150.00" min="0" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="price"></p>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('labModal')" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-violet-600 px-5 py-2 text-sm font-semibold text-white hover:bg-violet-700">Send Order</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 4. Enter Lab Result Modal --}}
    <div id="labResultModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">+ Enter Laboratory Result</h3>
                <button type="button" onclick="closeModal('labResultModal')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form data-url="{{ route('lab_results.store', $visit) }}" data-modal="labResultModal" onsubmit="submitModalForm(event)" class="space-y-4">
                <input type="hidden" name="test_name" id="labResultTestName">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Laboratory Request</label>
                    @if($visit->labRequests->isNotEmpty())
                        <select name="lab_request_id" onchange="updateLabTestName(this)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none mb-2">
                            <option value="" data-name="">-- Select from requested tests --</option>
                            @foreach($visit->labRequests as $requestItem)
                                <option value="{{ $requestItem->id }}" data-name="{{ $requestItem->test_name }}" {{ $requestItem->payment_status !== 'paid' ? 'disabled' : '' }}>
                                    {{ $requestItem->test_name }} (Order #{{ $requestItem->id }}) - {{ $requestItem->payment_status === 'paid' ? '✓ Paid' : '⚠️ Pending Payment' }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="lab_request_id"></p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Result / Findings *</label>
                    <textarea name="result_value" rows="3" placeholder="Enter test result findings (e.g. Hemoglobin: 14.2 g/dL, WBC: 6,500/mcL)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></textarea>
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="result_value"></p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Remarks (Optional)</label>
                    <input type="text" name="remarks" placeholder="Remarks or reference range" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('labResultModal')" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700">Save Result</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 5. Prescribe Medication Modal --}}
    <div id="prescriptionModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">+ Prescribe Medication</h3>
                <button type="button" onclick="closeModal('prescriptionModal')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form data-url="{{ route('prescriptions.store', $visit) }}" data-modal="prescriptionModal" onsubmit="submitModalForm(event)" class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Select Medicine *</label>
                    <select name="medicine_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                        <option value="">-- Select Medicine from Inventory --</option>
                        @foreach($medicines as $med)
                            <option value="{{ $med->id }}">{{ $med->name }} (Available: {{ $med->valid_stock_quantity }})</option>
                        @endforeach
                    </select>
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="medicine_id"></p>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Quantity *</label>
                        <input type="number" name="quantity" value="1" min="1" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="quantity"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Dosage</label>
                        <input type="text" name="dosage" value="1 tablet daily" placeholder="e.g. 500mg, 1 tablet" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="dosage"></p>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Frequency</label>
                        <input type="text" name="frequency" value="Once daily" placeholder="e.g. Twice daily, 8 hours" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Duration (Days)</label>
                        <input type="text" name="duration" value="7 days" placeholder="e.g. 5 days, 7 days" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="duration"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('prescriptionModal')" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-700">Prescribe</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 6. Final Diagnosis Modal --}}
    <div id="finalDxModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">+ Record Final Diagnosis</h3>
                    <p class="text-xs text-slate-500">Enter confirmed diagnosis after reviewing lab results.</p>
                </div>
                <button type="button" onclick="closeModal('finalDxModal')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form data-url="{{ route('consultations.final_dx', $visit) }}" data-modal="finalDxModal" onsubmit="submitModalForm(event)" class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Confirmed Final Diagnosis *</label>
                    <textarea name="final_dx" rows="4" placeholder="Enter confirmed diagnosis, clinical findings summary, or treatment notes..." minlength="3" maxlength="2000" pattern="^[\p{L}\s.,\-\?]+$" class="w-full rounded-2xl border border-slate-200 p-3 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none">{{ old('final_dx', $visit->consultation?->final_dx ?? '') }}</textarea>
                    <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="final_dx"></p>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('finalDxModal')" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">Save Final Diagnosis</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 7. Prerequisite Warning Modal --}}
    <div id="prerequisiteModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-lg rounded-3xl border border-rose-200 bg-white p-6 shadow-2xl">
            <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2 text-rose-600">
                    <span class="text-xl">🔒</span>
                    <h3 class="text-base font-bold text-slate-900">Final Diagnosis Locked</h3>
                </div>
                <button type="button" onclick="closeModal('prerequisiteModal')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <p class="text-sm text-slate-600 mb-4">Please complete the following required clinical steps before entering the Final Diagnosis:</p>
            <div class="space-y-2 rounded-2xl border border-rose-100 bg-rose-50/60 p-4 mb-5">
                @foreach($missingSteps as $missingStep)
                    <div class="flex items-center gap-2 text-sm font-semibold text-rose-800">
                        <span>❌</span>
                        <span>{{ $missingStep }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('prerequisiteModal')" class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">Understood</button>
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
     VANILLA JAVASCRIPT
═══════════════════════════════════════════════════════════════ --}}
<script>
(function() {
    'use strict';

    var CSRF_TOKEN = @json(csrf_token());

    // ──────────── Modal Management ────────────

    window.openModal = function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }
    };

    // Close on backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            e.target.classList.remove('open');
            document.body.style.overflow = '';
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.open').forEach(function(m) {
                m.classList.remove('open');
            });
            document.body.style.overflow = '';
        }
    });

    // ──────────── Lab Request → Test Name Auto-fill ────────────

    window.updateLabTestName = function(select) {
        var option = select.options[select.selectedIndex];
        var hidden = document.getElementById('labResultTestName');
        if (hidden && option && option.dataset.name) {
            hidden.value = option.dataset.name;
        }
    };

    // ──────────── Instant Pattern Field Validation ────────────

    function validatePatternField(el) {
        var form = el.closest('form');
        if (!form) return true;
        var errorEl = form.querySelector('[data-error="' + el.name + '"]');
        var patternStr = el.getAttribute('pattern');
        if (!patternStr) return true;

        var val = el.value;
        if (val.trim() === '') {
            if (errorEl && errorEl.textContent === 'Only letters and standard text are allowed.') {
                errorEl.textContent = '';
                errorEl.style.display = 'none';
            }
            return true;
        }

        try {
            var regex = new RegExp(patternStr, 'u');
            if (!regex.test(val)) {
                if (errorEl) {
                    errorEl.textContent = 'Only letters and standard text are allowed.';
                    errorEl.style.display = 'block';
                }
                return false;
            } else {
                if (errorEl && errorEl.textContent === 'Only letters and standard text are allowed.') {
                    errorEl.textContent = '';
                    errorEl.style.display = 'none';
                }
                return true;
            }
        } catch(err) {
            return true;
        }
    }

    document.addEventListener('input', function(e) {
        if (e.target && e.target.hasAttribute('pattern')) {
            validatePatternField(e.target);
        }
    });

    // ──────────── Generic Modal Form Submit ────────────

    window.submitModalForm = function(event) {
        event.preventDefault();

        var form  = event.target;
        var url   = form.dataset.url;
        var modal = form.dataset.modal;

        // Clear previous errors
        form.querySelectorAll('.field-error').forEach(function(el) {
            el.textContent = '';
            el.style.display = 'none';
        });

        // Instant client-side validation check
        var isClientValid = true;
        form.querySelectorAll('input[pattern], textarea[pattern]').forEach(function(el) {
            if (!validatePatternField(el)) {
                isClientValid = false;
            }
        });

        if (!isClientValid) {
            return;
        }

        // Collect form data from inputs
        var payload = {};
        form.querySelectorAll('input, textarea, select').forEach(function(el) {
            if (el.name && el.name !== '_token' && el.type !== 'submit') {
                payload[el.name] = el.value;
            }
        });

        // Disable submit button
        var btn = form.querySelector('button[type="submit"]');
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Saving...';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(function(response) {
            return response.json().then(function(data) {
                return { ok: response.ok, data: data };
            });
        })
        .then(function(result) {
            if (result.ok && result.data.success) {
                closeModal(modal);
                window.location.reload();
            } else if (result.data.errors) {
                var errors = result.data.errors;
                for (var field in errors) {
                    if (errors.hasOwnProperty(field)) {
                        var errorEl = form.querySelector('[data-error="' + field + '"]');
                        if (errorEl) {
                            errorEl.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                            errorEl.style.display = 'block';
                        }
                    }
                }
            } else if (result.data.message) {
                alert(result.data.message);
            }
        })
        .catch(function() {
            alert('Network error. Please try again.');
        })
        .finally(function() {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    };

})();
</script>
@endsection