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

    $user = auth()->user();
    $isDoctor = $user && $user->hasRole(['admin', 'doctor']);
    $isNurseRole = $user && $user->hasRole(['admin', 'nurse']);
    $isLabRole = $user && $user->hasRole(['admin', 'lab', 'lab_tech']);

    $isNurse = $user && $user->hasRole('nurse');
    $isReception = $user && $user->hasRole(['reception', 'receptionist']);
    $isLabTech = $user && $user->hasRole(['lab', 'lab_tech']);
    $isPharmacist = $user && $user->hasRole(['pharmacy', 'pharmacist']);
    $isRestrictedRole = $isNurse || $isReception || $isLabTech || $isPharmacist;

    $rxData = [
        'visit_id' => $visit->id,
        'date' => date('M d, Y - h:i A'),
        'patient_name' => $visit->patient->name ?? 'N/A',
        'card_number' => $visit->patient->card_number ?? '-',
        'gender' => ucfirst($visit->patient->gender ?? '-'),
        'age' => $visit->patient->age ?? '-',
        'phone' => $visit->patient->phone ?? '-',
        'doctor_name' => $visit->consultation?->doctor?->name ?? auth()->user()?->name ?? 'Attending Doctor',
        'diagnosis' => $visit->consultation?->final_dx ?? $visit->consultation?->provisional_dx ?? 'General Medical Consultation',
        'vitals' => $visit->vitalSign ? [
            'bp' => ($visit->vitalSign->systolic_bp ?? '-') . '/' . ($visit->vitalSign->diastolic_bp ?? '-') . ' mmHg',
            'pulse' => ($visit->vitalSign->pulse_rate ?? '-') . ' bpm',
            'temp' => ($visit->vitalSign->temperature ?? '-') . ' °C',
            'weight' => ($visit->vitalSign->weight ?? '-') . ' kg',
            'spo2' => ($visit->vitalSign->spo2 ?? '-') . ' %',
        ] : null,
        'items' => $visit->prescriptions->map(function($p) {
            return [
                'name' => $p->medicine->name ?? 'Medication Item',
                'generic' => $p->medicine->generic_name ?? '',
                'dosage' => $p->dosage,
                'frequency' => $p->frequency,
                'duration' => $p->duration,
                'quantity' => $p->quantity,
            ];
        })->values()->all(),
    ];
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
    @if(session('success') || session('error') || (isset($errors) && (($errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any()) || (is_array($errors) && !empty($errors)))))
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-1">
            @if(session('success'))
                <p class="text-sm font-semibold text-emerald-700">✓ {{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="text-sm font-semibold text-rose-700">✕ {{ session('error') }}</p>
            @endif
            @if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
                @foreach($errors->all() as $error)
                    <p class="text-sm font-semibold text-rose-700">✕ {{ $error }}</p>
                @endforeach
            @elseif(isset($errors) && is_array($errors))
                @foreach($errors as $error)
                    <p class="text-sm font-semibold text-rose-700">✕ {{ is_array($error) ? implode(', ', $error) : $error }}</p>
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

                {{-- + Add Vitals (Nurse & Admin only - Hidden for Doctor) --}}
                @if(in_array(auth()->user()?->role, ['admin', 'nurse']))
                <button type="button" onclick="openModal('vitalModal')" class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    + Add Vitals
                </button>
                @endif

                {{-- Doctor Actions --}}
                @if(in_array(auth()->user()?->role, ['admin', 'doctor']))
                <button type="button" onclick="openModal('doctorModal')" class="rounded-2xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    + Doctor Note
                </button>
                <button type="button" onclick="openModal('labModal')" class="rounded-2xl bg-violet-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-700">
                    + Order Lab
                </button>
                @endif

                {{-- + Enter Lab Result (Lab Tech & Admin only - Hidden for Doctor) --}}
                @if(in_array(auth()->user()?->role, ['admin', 'lab', 'lab_tech']))
                <button type="button" onclick="openModal('labResultModal')" class="rounded-2xl bg-teal-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-700">
                    + Enter Lab Result
                </button>
                @endif

                {{-- + Prescribe Medicine --}}
                @if(in_array(auth()->user()?->role, ['admin', 'doctor']))
                <button type="button" onclick="openModal('prescriptionModal')" class="rounded-2xl bg-amber-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-700">
                    + Prescribe Medicine
                </button>
                @endif

                {{-- 🖨️ Print Prescription --}}
                @if($visit->prescriptions->isNotEmpty())
                <button type="button" onclick="printPrescriptionSlip()" class="rounded-2xl bg-slate-800 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-900 flex items-center gap-1.5">
                    🖨️ Print Prescription
                </button>
                @endif

                {{-- Final Diagnosis --}}
                @if(in_array(auth()->user()?->role, ['admin', 'doctor']))
                    @if($prerequisitesMet)
                        <button type="button" onclick="openModal('finalDxModal')" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700">
                            ✓ Final Diagnosis
                        </button>
                    @else
                        <button type="button" onclick="openModal('prerequisiteModal')" class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-2.5 text-xs font-bold text-rose-700 shadow-sm transition hover:bg-rose-100 flex items-center gap-1.5">
                            🔒 Final Diagnosis (Locked)
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════ Main Layout Grid ═══════════════════ --}}
    <div class="grid gap-6 {{ $isRestrictedRole ? 'grid-cols-1' : 'lg:grid-cols-3' }}">

        {{-- Left Column --}}
        <div class="space-y-6 {{ $isRestrictedRole ? 'col-span-1' : 'lg:col-span-2' }}">

            @if(!$isReception && !$isLabTech && !$isPharmacist)
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
                    @php
                        $vs = $visit->vitalSign;
                        $sysAbnormal = $vs->systolic_bp !== null && ($vs->systolic_bp > 140 || $vs->systolic_bp < 90);
                        $diaAbnormal = $vs->diastolic_bp !== null && ($vs->diastolic_bp > 90 || $vs->diastolic_bp < 60);
                        $bpAbnormal = $sysAbnormal || $diaAbnormal;
                        $pulseAbnormal = $vs->pulse_rate !== null && ($vs->pulse_rate > 110 || $vs->pulse_rate < 50);
                        $tempAbnormal = $vs->temperature !== null && ($vs->temperature > 38.5 || $vs->temperature < 35.0);
                        $spo2Abnormal = $vs->spo2 !== null && ($vs->spo2 < 92);
                        $hasAnyAbnormal = $bpAbnormal || $pulseAbnormal || $tempAbnormal || $spo2Abnormal;
                    @endphp

                    @if($hasAnyAbnormal)
                        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50/80 p-3 text-xs font-semibold text-rose-800 flex items-center justify-between shadow-sm">
                            <span class="flex items-center gap-1.5">
                                <span class="text-sm">⚠️</span> CRITICAL / ABNORMAL VITAL SIGNS DETECTED
                            </span>
                            <span class="rounded-full bg-rose-600 px-2.5 py-0.5 text-[10px] font-bold text-white uppercase">Critical Alert</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <!-- BP -->
                        <div class="rounded-2xl p-3 transition {{ $bpAbnormal ? 'bg-rose-50 border border-rose-200 text-rose-900' : 'bg-slate-50' }}">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase {{ $bpAbnormal ? 'text-rose-700' : 'text-slate-400' }}">Blood Pressure</p>
                                @if($bpAbnormal)
                                    <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">Alert</span>
                                @endif
                            </div>
                            <p class="mt-1 text-base font-bold {{ $bpAbnormal ? 'text-rose-900' : 'text-slate-900' }}">{{ $vs->systolic_bp ?? '-' }}/{{ $vs->diastolic_bp ?? '-' }} <span class="text-xs font-normal {{ $bpAbnormal ? 'text-rose-600' : 'text-slate-500' }}">mmHg</span></p>
                        </div>

                        <!-- Pulse -->
                        <div class="rounded-2xl p-3 transition {{ $pulseAbnormal ? 'bg-amber-50 border border-amber-200 text-amber-900' : 'bg-slate-50' }}">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase {{ $pulseAbnormal ? 'text-amber-700' : 'text-slate-400' }}">Pulse Rate</p>
                                @if($pulseAbnormal)
                                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">Alert</span>
                                @endif
                            </div>
                            <p class="mt-1 text-base font-bold {{ $pulseAbnormal ? 'text-amber-900' : 'text-slate-900' }}">{{ $vs->pulse_rate ?? '-' }} <span class="text-xs font-normal {{ $pulseAbnormal ? 'text-amber-600' : 'text-slate-500' }}">bpm</span></p>
                        </div>

                        <!-- Temp -->
                        <div class="rounded-2xl p-3 transition {{ $tempAbnormal ? 'bg-rose-50 border border-rose-200 text-rose-900' : 'bg-slate-50' }}">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase {{ $tempAbnormal ? 'text-rose-700' : 'text-slate-400' }}">Temperature</p>
                                @if($tempAbnormal)
                                    <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">Fever/Hypo</span>
                                @endif
                            </div>
                            <p class="mt-1 text-base font-bold {{ $tempAbnormal ? 'text-rose-900' : 'text-slate-900' }}">{{ $vs->temperature ?? '-' }} <span class="text-xs font-normal {{ $tempAbnormal ? 'text-rose-600' : 'text-slate-500' }}">°C</span></p>
                        </div>

                        <!-- SpO2 -->
                        <div class="rounded-2xl p-3 transition {{ $spo2Abnormal ? 'bg-rose-50 border border-rose-200 text-rose-900' : 'bg-slate-50' }}">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase {{ $spo2Abnormal ? 'text-rose-700' : 'text-slate-400' }}">SpO2</p>
                                @if($spo2Abnormal)
                                    <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">Low O2</span>
                                @endif
                            </div>
                            <p class="mt-1 text-base font-bold {{ $spo2Abnormal ? 'text-rose-900' : 'text-slate-900' }}">{{ $vs->spo2 ?? '-' }} <span class="text-xs font-normal {{ $spo2Abnormal ? 'text-rose-600' : 'text-slate-500' }}">%</span></p>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                        No vital signs recorded yet.
                    </div>
                @endif
            </div>
            @endif

            @if(!$isNurse && !$isReception && !$isLabTech && !$isPharmacist)
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
            @endif

            @if(!$isNurse && !$isPharmacist)
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
                                                @if(in_array(auth()->user()->role, ['admin', 'reception', 'receptionist', 'cashier']))
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
            @endif

            @if(!$isNurse && !$isReception && !$isPharmacist)
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
                        No laboratory results recorded yet.
                    </div>
                @endif
            </div>
            @endif
        </div>

        @if(!$isNurse && !$isReception && !$isLabTech)
        {{-- Right Column (1 Col) --}}
        <div class="space-y-6">

            @if(!$isPharmacist)
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
                        @endif
                    </div>
                @endif
            </div>
            @endif

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
                        @if($visit->prescriptions->isNotEmpty())
                        <button type="button" onclick="printPrescriptionSlip()" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-amber-700">
                            🖨️ Print Slip
                        </button>
                        @endif
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                            {{ $visit->prescriptions->count() }} {{ Str::plural('Item', $visit->prescriptions->count()) }}
                        </span>
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
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>


    {{-- ═══════════════════════════════════════════════════════════
         MODALS
    ═══════════════════════════════════════════════════════════════ --}}

    @if(!$isReception)
    {{-- 1. Vital Signs Modal --}}
    <div id="vitalModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">+ Add Vital Signs</h3>
                <button type="button" onclick="closeModal('vitalModal')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form 
                data-url="{{ route('vitals.store', $visit) }}" 
                data-modal="vitalModal" 
                onsubmit="submitModalForm(event)" 
                x-data="{
                    systolic: '{{ $visit->vitalSign?->systolic_bp ?? '' }}',
                    diastolic: '{{ $visit->vitalSign?->diastolic_bp ?? '' }}',
                    pulse: '{{ $visit->vitalSign?->pulse_rate ?? '' }}',
                    temp: '{{ $visit->vitalSign?->temperature ?? '' }}',
                    spo2: '{{ $visit->vitalSign?->spo2 ?? '' }}',
                    
                    isSystolicAlert() { return this.systolic !== '' && (Number(this.systolic) > 140 || Number(this.systolic) < 90); },
                    isDiastolicAlert() { return this.diastolic !== '' && (Number(this.diastolic) > 90 || Number(this.diastolic) < 60); },
                    isPulseAlert() { return this.pulse !== '' && (Number(this.pulse) > 110 || Number(this.pulse) < 50); },
                    isTempAlert() { return this.temp !== '' && (Number(this.temp) > 38.5 || Number(this.temp) < 35); },
                    isSpo2Alert() { return this.spo2 !== '' && Number(this.spo2) < 92; },
                    hasAnyAlert() { return this.isSystolicAlert() || this.isDiastolicAlert() || this.isPulseAlert() || this.isTempAlert() || this.isSpo2Alert(); }
                }"
                class="space-y-4"
            >
                <template x-if="hasAnyAlert()">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-800 flex items-center gap-2">
                        <span>⚠️</span> Emergency / Critical vital signs detected. Form submission is permitted for clinical recording.
                    </div>
                </template>

                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Systolic BP (mmHg)</label>
                        <input 
                            type="number" 
                            min="0"
                            name="systolic_bp" 
                            x-model="systolic"
                            placeholder="e.g. 120" 
                            :class="isSystolicAlert() ? 'border-rose-400 bg-rose-50/50 text-rose-900' : 'border-slate-200'"
                            class="w-full rounded-xl border px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none transition"
                        >
                        <template x-if="isSystolicAlert()">
                            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Systolic (< 90 or > 140)</span>
                        </template>
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="systolic_bp"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Diastolic BP (mmHg)</label>
                        <input 
                            type="number" 
                            min="0"
                            name="diastolic_bp" 
                            x-model="diastolic"
                            placeholder="e.g. 80" 
                            :class="isDiastolicAlert() ? 'border-rose-400 bg-rose-50/50 text-rose-900' : 'border-slate-200'"
                            class="w-full rounded-xl border px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none transition"
                        >
                        <template x-if="isDiastolicAlert()">
                            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Diastolic (< 60 or > 90)</span>
                        </template>
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="diastolic_bp"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Pulse Rate (bpm)</label>
                        <input 
                            type="number" 
                            min="0"
                            name="pulse_rate" 
                            x-model="pulse"
                            placeholder="e.g. 72" 
                            :class="isPulseAlert() ? 'border-amber-400 bg-amber-50/50 text-amber-900' : 'border-slate-200'"
                            class="w-full rounded-xl border px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none transition"
                        >
                        <template x-if="isPulseAlert()">
                            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">⚠️ Pulse (< 50 or > 110)</span>
                        </template>
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="pulse_rate"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Respiratory Rate (breaths/min)</label>
                        <input 
                            type="number" 
                            min="0"
                            name="respiratory_rate" 
                            value="{{ $visit->vitalSign?->respiratory_rate ?? '' }}" 
                            placeholder="e.g. 16" 
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none transition"
                        >
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="respiratory_rate"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Temperature (°C)</label>
                        <input 
                            type="number" 
                            step="0.1" 
                            min="0"
                            name="temperature" 
                            x-model="temp"
                            placeholder="e.g. 36.6" 
                            :class="isTempAlert() ? 'border-rose-400 bg-rose-50/50 text-rose-900' : 'border-slate-200'"
                            class="w-full rounded-xl border px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none transition"
                        >
                        <template x-if="isTempAlert()">
                            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Temp (< 35 or > 38.5°C)</span>
                        </template>
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="temperature"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">SpO2 (%)</label>
                        <input 
                            type="number" 
                            min="0"
                            max="100"
                            name="spo2" 
                            x-model="spo2"
                            placeholder="e.g. 98" 
                            :class="isSpo2Alert() ? 'border-rose-400 bg-rose-50/50 text-rose-900' : 'border-slate-200'"
                            class="w-full rounded-xl border px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none transition"
                        >
                        <template x-if="isSpo2Alert()">
                            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Low SpO2 (< 92%)</span>
                        </template>
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="spo2"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Weight (kg)</label>
                        <input type="number" step="0.1" min="0" name="weight" value="{{ $visit->vitalSign?->weight ?? '' }}" placeholder="e.g. 70.5" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="weight"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Height (m/cm)</label>
                        <input type="number" step="0.01" min="0" name="height" value="{{ $visit->vitalSign?->height ?? '' }}" placeholder="e.g. 1.75" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
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
    @endif

    @if($isDoctor)
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
    @endif

    {{-- 3. Order Lab Test Modal --}}
    @if($isDoctor)
    <div id="labModal" class="modal-backdrop">
        <div class="modal-panel w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">Order Laboratory Test</h3>
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
    @endif

    {{-- 4. Enter Lab Result Modal --}}
    @if($isLabRole)
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
    @endif

    {{-- 5. Prescribe Medication Modal --}}
    @if($isDoctor)
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
                        <select name="dosage" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                            <option value="1 tablet" selected>1 tablet</option>
                            <option value="2 tablets">2 tablets</option>
                            <option value="1 capsule">1 capsule</option>
                            <option value="2 capsules">2 capsules</option>
                            <option value="5 ml">5 ml</option>
                            <option value="10 ml">10 ml</option>
                            <option value="15 ml">15 ml</option>
                            <option value="1 injection">1 injection</option>
                            <option value="Apply topically">Apply topically</option>
                            <option value="1-2 drops">1-2 drops</option>
                        </select>
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="dosage"></p>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Frequency</label>
                        <select name="frequency" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                            <option value="Once daily (OD)" selected>Once daily (OD)</option>
                            <option value="Twice daily (BID)">Twice daily (BID)</option>
                            <option value="Three times daily (TID)">Three times daily (TID)</option>
                            <option value="Four times daily (QID)">Four times daily (QID)</option>
                            <option value="Every 4 hours">Every 4 hours</option>
                            <option value="Every 6 hours">Every 6 hours</option>
                            <option value="Every 8 hours">Every 8 hours</option>
                            <option value="Every 12 hours">Every 12 hours</option>
                            <option value="As needed (PRN)">As needed (PRN)</option>
                            <option value="Stat">Stat</option>
                        </select>
                        <p class="field-error text-rose-600 text-xs font-semibold mt-1" data-error="frequency"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Duration</label>
                        <select name="duration" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                            <option value="1 day">1 day</option>
                            <option value="3 days">3 days</option>
                            <option value="5 days">5 days</option>
                            <option value="7 days" selected>7 days</option>
                            <option value="10 days">10 days</option>
                            <option value="14 days">14 days</option>
                            <option value="21 days">21 days</option>
                            <option value="30 days">30 days</option>
                            <option value="60 days">60 days</option>
                            <option value="90 days">90 days</option>
                            <option value="Single dose">Single dose</option>
                        </select>
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
    @endif

    {{-- 6. Final Diagnosis Modal --}}
    @if($isDoctor)
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
    @endif

    {{-- 7. Prerequisite Warning Modal --}}
    @if($isDoctor)
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
    @endif

</div>

{{-- ═══════════════════════════════════════════════════════════
     VANILLA JAVASCRIPT
═══════════════════════════════════════════════════════════════ --}}
<script>
(function() {
    'use strict';

    var CSRF_TOKEN = @json(csrf_token());

    // ──────────── Standalone Prescription Window Printer ────────────

    window.printPrescriptionSlip = function() {
        var data = @json($rxData);
        if (!data.items || data.items.length === 0) {
            alert('No prescriptions available to print.');
            return;
        }

        var win = window.open('', '_blank', 'width=850,height=900,top=100,left=100');
        if (!win) {
            alert('Please allow popups for this site to print the prescription.');
            return;
        }

        var itemsHtml = '';
        data.items.forEach(function(item, idx) {
            itemsHtml += '<tr style="border-bottom: 1px solid #e2e8f0;">' +
                '<td style="padding: 10px; text-align: center; font-weight: bold; color: #64748b;">' + (idx + 1) + '</td>' +
                '<td style="padding: 10px;">' +
                    '<strong style="color: #0f172a; font-size: 13px;">' + item.name + '</strong>' +
                    (item.generic ? '<div style="font-size: 10px; color: #64748b; font-style: italic;">Generic: ' + item.generic + '</div>' : '') +
                '</td>' +
                '<td style="padding: 10px; color: #334155; font-weight: 600;">' + item.dosage + '</td>' +
                '<td style="padding: 10px; color: #334155;">' + item.frequency + '</td>' +
                '<td style="padding: 10px; color: #334155;">' + item.duration + '</td>' +
                '<td style="padding: 10px; text-align: center; font-weight: bold; color: #0f172a; background-color: #fef3c7;">' + item.quantity + '</td>' +
            '</tr>';
        });

        var vitalsHtml = '';
        if (data.vitals) {
            vitalsHtml = '<div style="margin-top: 8px; font-size: 11px; color: #475569;">' +
                '<strong style="color: #64748b; text-transform: uppercase;">Vitals Summary:</strong> ' +
                'BP: <strong>' + data.vitals.bp + '</strong> | Pulse: <strong>' + data.vitals.pulse + '</strong> | Temp: <strong>' + data.vitals.temp + '</strong> | Weight: <strong>' + data.vitals.weight + '</strong>' +
            '</div>';
        }

        var html = '<!DOCTYPE html><html><head>' +
            '<title>Prescription Slip #' + data.visit_id + '</title>' +
            '<meta charset="utf-8">' +
            '<style>' +
                '@page { size: A4 portrait; margin: 15mm; }' +
                'body { font-family: system-ui, -apple-system, sans-serif; color: #0f172a; background: #fff; margin: 0; padding: 20px; line-height: 1.4; }' +
                '.rx-container { max-width: 800px; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 12px; padding: 30px; box-sizing: border-box; }' +
                '.header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #0f172a; padding-bottom: 15px; }' +
                '.patient-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px; }' +
                '.items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 12px; border: 1px solid #e2e8f0; }' +
                '.items-table th { background: #f1f5f9; padding: 10px; text-align: left; font-size: 10px; font-weight: 800; color: #475569; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }' +
                '.advice-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; font-size: 11px; color: #1e3a8a; margin-bottom: 30px; }' +
                '.footer-grid { display: table; width: 100%; margin-top: 30px; border-top: 1px solid #cbd5e1; padding-top: 20px; }' +
                '.footer-col { display: table-cell; width: 50%; vertical-align: top; font-size: 11px; }' +
                '@media print { body { padding: 0; } .rx-container { border: none; padding: 0; } }' +
            '</style>' +
        '</head><body>' +
            '<div class="rx-container">' +
                '<table class="header-table"><tr>' +
                    '<td style="vertical-align: top;">' +
                        '<div style="display: flex; align-items: center;">' +
                            '<div style="width: 44px; height: 44px; background: #047857; color: white; border-radius: 10px; text-align: center; line-height: 44px; font-size: 22px; font-weight: bold; display: inline-block;">⚕️</div>' +
                            '<div style="display: inline-block; vertical-align: top; margin-left: 12px;">' +
                                '<h1 style="font-size: 20px; font-weight: 900; margin: 0; color: #0f172a; text-transform: uppercase; letter-spacing: -0.5px;">HOPE CARE MEDICAL CENTER</h1>' +
                                '<div style="font-size: 12px; font-weight: 600; color: #475569;">Specialty Medical Clinic & Diagnostic Center</div>' +
                                '<div style="font-size: 11px; color: #64748b; margin-top: 2px;">Bole Road, Addis Ababa, Ethiopia • Tel: +251 911 234 567</div>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td style="vertical-align: top; text-align: right;">' +
                        '<div style="background: #fef3c7; color: #78350f; border: 1px solid #fde68a; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 6px; display: inline-block; text-transform: uppercase;">Official Prescription</div>' +
                        '<div style="font-size: 12px; font-weight: bold; margin-top: 6px; color: #334155;">Rx Ref: <span style="font-family: monospace;">#RX-' + data.visit_id + '</span></div>' +
                        '<div style="font-size: 11px; color: #64748b; margin-top: 2px;">Date: ' + data.date + '</div>' +
                    '</td>' +
                '</tr></table>' +

                '<div class="patient-box">' +
                    '<table style="width: 100%; border-collapse: collapse; font-size: 12px;"><tr>' +
                        '<td style="width: 50%; vertical-align: top;">' +
                            '<div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">PATIENT NAME</div>' +
                            '<div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 2px;">' + data.patient_name + '</div>' +
                            '<div style="margin-top: 8px;">' +
                                '<span style="font-size: 10px; font-weight: 800; color: #64748b;">CARD #: </span><strong style="color: #334155;">' + data.card_number + '</strong>' +
                                ' &nbsp;|&nbsp; ' +
                                '<span style="font-size: 10px; font-weight: 800; color: #64748b;">AGE/GENDER: </span><strong style="color: #334155;">' + data.age + ' Yrs / ' + data.gender + '</strong>' +
                            '</div>' +
                        '</td>' +
                        '<td style="width: 50%; vertical-align: top;">' +
                            '<div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">DIAGNOSIS & PHYSICIAN</div>' +
                            '<div style="font-size: 13px; font-weight: 700; color: #b45309; margin-top: 2px;">' + data.diagnosis + '</div>' +
                            '<div style="font-size: 11px; color: #475569; margin-top: 2px;">Doctor: <strong>Dr. ' + data.doctor_name + '</strong></div>' +
                            vitalsHtml +
                        '</td>' +
                    '</tr></table>' +
                '</div>' +

                '<div style="margin-bottom: 12px; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px;">' +
                    '<span style="font-size: 28px; font-weight: 900; font-family: Georgia, serif; font-style: italic; color: #0f172a;">℞</span>' +
                    '<span style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #0f172a; margin-left: 8px;">PRESCRIBED MEDICATIONS</span>' +
                '</div>' +

                '<table class="items-table"><thead><tr>' +
                    '<th style="width: 30px; text-align: center;">#</th>' +
                    '<th>Medication Name</th>' +
                    '<th>Dosage Strength</th>' +
                    '<th>Frequency</th>' +
                    '<th>Duration</th>' +
                    '<th style="text-align: center;">Total Qty</th>' +
                '</tr></thead><tbody>' + itemsHtml + '</tbody></table>' +

                '<div class="advice-box">' +
                    '<div style="font-weight: 800; text-transform: uppercase; font-size: 10px; color: #1d4ed8; margin-bottom: 4px;">Special Patient Instructions & Advice:</div>' +
                    '<ul style="margin: 0; padding-left: 18px; line-height: 1.5;">' +
                        '<li>Take medications strictly as instructed by the physician.</li>' +
                        '<li>Complete the full duration of treatment unless advised otherwise by doctor.</li>' +
                        '<li>Store medicines in a cool, dry place away from direct sunlight & children.</li>' +
                    '</ul>' +
                '</div>' +

                '<div class="footer-grid">' +
                    '<div class="footer-col">' +
                        '<div style="font-weight: 800; color: #475569; text-transform: uppercase; font-size: 10px;">Prescribing Physician</div>' +
                        '<div style="margin-top: 40px; border-top: 1px solid #94a3b8; width: 80%; padding-top: 4px;">' +
                            '<div style="font-weight: bold; color: #0f172a;">Dr. ' + data.doctor_name + '</div>' +
                            '<div style="color: #64748b; font-size: 10px;">License No: REG-MD-' + data.visit_id + '</div>' +
                            '<div style="color: #94a3b8; font-size: 10px; font-style: italic; margin-top: 2px;">Signature & Stamp: ________________</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="footer-col" style="text-align: right;">' +
                        '<div style="font-weight: 800; color: #475569; text-transform: uppercase; font-size: 10px;">Pharmacy Dispensing Seal</div>' +
                        '<div style="margin-top: 40px; border-top: 1px solid #94a3b8; width: 80%; margin-left: 20%; padding-top: 4px;">' +
                            '<div style="font-weight: bold; color: #0f172a;">Licensed Pharmacist</div>' +
                            '<div style="color: #64748b; font-size: 10px;">Dispense Date: ____ / ____ / 2026</div>' +
                            '<div style="color: #94a3b8; font-size: 10px; font-style: italic; margin-top: 2px;">Verified & Dispensed: ________________</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div style="text-align: center; margin-top: 25px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 10px; color: #94a3b8;">' +
                    'This official prescription is valid for 7 days from issue date. • Hope Care Medical System' +
                '</div>' +
            '</div>' +
            '<script>window.onload = function() { window.print(); };<\/script>' +
        '</body></html>';

        win.document.open();
        win.document.write(html);
        win.document.close();
    };

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