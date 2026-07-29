@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Back Button -->
    <a href="{{ route('patients.show', $visit->patient_id) }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 text-sm font-semibold transition">
        &lt; Back to Patient Details
    </a>

    <!-- Patient Brief Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex justify-between items-center text-slate-800">
        <div>
            <h2 class="font-bold text-lg text-blue-950">{{ $visit->patient->full_name }}</h2>
            <p class="text-xs text-blue-700 font-medium">Card No: {{ $visit->patient->formatted_card_number }} | Age/Sex: {{ $visit->patient->age }} Yrs / {{ ucfirst($visit->patient->gender) }}</p>
        </div>
        <span class="px-3 py-1 bg-blue-600 text-white rounded-full text-xs font-bold">
            Visit Date: {{ $visit->created_at->format('Y-m-d') }}
        </span>
    </div>

    <!-- Vital Signs Form Card -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-6 flex items-center gap-2">
            🩺 Vital Signs Recording Form
        </h3>

        <form action="{{ route('vitals.store', $visit) }}" method="POST" class="space-y-6">
            @csrf

            <!-- Blood Pressure & Pulse -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Systolic BP (mmHg)</label>
                    <input type="number" name="systolic_bp" value="{{ old('systolic_bp', $visit->vitalSign->systolic_bp ?? '') }}" placeholder="e.g. 120" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Diastolic BP (mmHg)</label>
                    <input type="number" name="diastolic_bp" value="{{ old('diastolic_bp', $visit->vitalSign->diastolic_bp ?? '') }}" placeholder="e.g. 80" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pulse Rate (bpm)</label>
                    <input type="number" name="pulse_rate" value="{{ old('pulse_rate', $visit->vitalSign->pulse_rate ?? '') }}" placeholder="e.g. 72" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <!-- Temp, SpO2 & Respiratory -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Temperature (°C)</label>
                    <input type="number" step="0.1" name="temperature" value="{{ old('temperature', $visit->vitalSign->temperature ?? '') }}" placeholder="e.g. 36.6" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">SpO2 (%)</label>
                    <input type="number" name="spo2" value="{{ old('spo2', $visit->vitalSign->spo2 ?? '') }}" placeholder="e.g. 98" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Resp. Rate (breaths/min)</label>
                    <input type="number" name="respiratory_rate" value="{{ old('respiratory_rate', $visit->vitalSign->respiratory_rate ?? '') }}" placeholder="e.g. 18" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <!-- Weight & Height -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Weight (kg)</label>
                    <input type="number" step="0.1" name="weight" value="{{ old('weight', $visit->vitalSign->weight ?? '') }}" placeholder="e.g. 70.5" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Height (cm)</label>
                    <input type="number" step="0.1" name="height" value="{{ old('height', $visit->vitalSign->height ?? '') }}" placeholder="e.g. 175" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-slate-100">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition shadow-sm">
                    💾 Save Vital Signs
                </button>
            </div>
        </form>
    </div>
</div>
@endsection