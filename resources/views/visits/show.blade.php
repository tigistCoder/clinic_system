@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    showVitalModal: false,
    showDoctorModal: false,
    showLabModal: false,
    showPrescriptionModal: false
}">
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-700">Visit #{{ $visit->id }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">{{ ucfirst($visit->urgency_status ?? 'normal') }}</span>
                </div>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $visit->patient->full_name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $visit->patient->age }} yrs • {{ ucfirst($visit->patient->gender) }} • {{ $visit->patient->phone_number }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="showVitalModal = true" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">+ Add Vitals</button>
                <button @click="showDoctorModal = true" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Doctor Note</button>
                <button @click="showLabModal = true" class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">Order Lab</button>
                <button @click="showPrescriptionModal = true" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700">Prescribe</button>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Patient workflow</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ ucfirst($visit->visit_status ?? 'triage') }}</span>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Nurse</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">Record vital signs</p>
                    </div>
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Doctor</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">Add diagnosis and findings</p>
                    </div>
                    <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Lab / Pharmacy</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">Order tests and medications</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Vital signs</h2>
                    @if($visit->vitalSign)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Captured</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Pending</span>
                    @endif
                </div>
                @if($visit->vitalSign)
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-semibold uppercase text-slate-500">BP</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ $visit->vitalSign->systolic_bp ?? '-' }}/{{ $visit->vitalSign->diastolic_bp ?? '-' }} mmHg</p></div>
                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-semibold uppercase text-slate-500">Pulse</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ $visit->vitalSign->pulse_rate ?? '-' }} bpm</p></div>
                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-semibold uppercase text-slate-500">Temp</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ $visit->vitalSign->temperature ?? '-' }} °C</p></div>
                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-semibold uppercase text-slate-500">SpO2</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ $visit->vitalSign->spo2 ?? '-' }} %</p></div>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No vital signs recorded yet.</div>
                @endif
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Consultation</h2>
                    @if($visit->consultation)
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">Completed</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Pending</span>
                    @endif
                </div>
                @if($visit->consultation)
                    <div class="space-y-3 text-sm text-slate-600">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Chief complaint</p><p class="mt-1 font-semibold text-slate-800">{{ $visit->consultation->chief_complaint }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">History</p><p class="mt-1">{{ $visit->consultation->history_present_illness ?? '—' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Provisional diagnosis</p><p class="mt-1 font-semibold text-blue-700">{{ $visit->consultation->provisional_dx ?? '—' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Final diagnosis</p><p class="mt-1 font-semibold text-emerald-700">{{ $visit->consultation->final_dx ?? 'Pending' }}</p></div>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No doctor consultation yet.</div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-800">Laboratory requests</h2>
                @if($visit->labRequests->isNotEmpty())
                    <div class="mt-4 space-y-3">
                        @foreach($visit->labRequests as $lab)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <div class="flex items-center justify-between">
                                    <p class="font-semibold text-slate-800">{{ $lab->test_name }}</p>
                                    <span class="rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-semibold text-violet-700">{{ $lab->status }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">Price: {{ number_format($lab->price, 2) }} ETB</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No lab orders yet.</div>
                @endif
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-800">Prescriptions</h2>
                @if($visit->prescriptions->isNotEmpty())
                    <div class="mt-4 space-y-3">
                        @foreach($visit->prescriptions as $prescription)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <p class="font-semibold text-slate-800">{{ $prescription->medicine->name ?? 'Medicine' }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $prescription->dosage }} • {{ $prescription->frequency }} • {{ $prescription->duration }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No prescriptions yet.</div>
                @endif
            </div>
        </div>
    </div>

    <div x-show="showVitalModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="showVitalModal = false" class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Add vital signs</h3>
                <button @click="showVitalModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="{{ route('vitals.store', $visit) }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid gap-3 md:grid-cols-2">
                    <input type="number" name="systolic_bp" placeholder="Systolic BP" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    <input type="number" name="diastolic_bp" placeholder="Diastolic BP" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    <input type="number" name="pulse_rate" placeholder="Pulse rate" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    <input type="number" name="respiratory_rate" placeholder="Respiratory rate" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    <input type="number" step="0.1" name="temperature" placeholder="Temperature" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    <input type="number" name="spo2" placeholder="SpO2" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    <input type="number" step="0.1" name="weight" placeholder="Weight (kg)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    <input type="number" step="0.1" name="height" placeholder="Height (cm)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showVitalModal = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showDoctorModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="showDoctorModal = false" class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Doctor consultation</h3>
                <button @click="showDoctorModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="{{ route('consultations.store', $visit) }}" method="POST" class="space-y-4">
                @csrf
                <input type="text" name="chief_complaint" placeholder="Chief complaint" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                <textarea name="history_present_illness" rows="3" placeholder="History of present illness" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                <textarea name="physical_exam" rows="3" placeholder="Physical exam" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                <div class="grid gap-3 md:grid-cols-2">
                    <input type="text" name="provisional_dx" placeholder="Provisional diagnosis" required class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <input type="text" name="final_dx" placeholder="Final diagnosis" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showDoctorModal = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showLabModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="showLabModal = false" class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Order laboratory test</h3>
                <button @click="showLabModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="{{ route('lab_requests.store', $visit) }}" method="POST" class="space-y-4">
                @csrf
                <input type="text" name="test_name" placeholder="Test name" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
                <input type="number" step="0.01" name="price" placeholder="Price (ETB)" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showLabModal = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white">Send order</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showPrescriptionModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="showPrescriptionModal = false" class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Prescribe medication</h3>
                <button @click="showPrescriptionModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="{{ route('prescriptions.store', $visit) }}" method="POST" class="space-y-4">
                @csrf
                <input type="text" name="medicine_name" placeholder="Medicine name" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                <div class="grid gap-3 md:grid-cols-3">
                    <input type="text" name="dosage" placeholder="Dosage" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                    <input type="text" name="frequency" placeholder="Frequency" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                    <input type="text" name="duration" placeholder="Duration" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showPrescriptionModal = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white">Save prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
