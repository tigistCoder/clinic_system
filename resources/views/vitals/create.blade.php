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

        <form 
            action="{{ route('vitals.store', $visit) }}" 
            method="POST" 
            x-data="{
                systolic: '{{ old('systolic_bp', $visit->vitalSign->systolic_bp ?? '') }}',
                diastolic: '{{ old('diastolic_bp', $visit->vitalSign->diastolic_bp ?? '') }}',
                pulse: '{{ old('pulse_rate', $visit->vitalSign->pulse_rate ?? '') }}',
                temp: '{{ old('temperature', $visit->vitalSign->temperature ?? '') }}',
                spo2: '{{ old('spo2', $visit->vitalSign->spo2 ?? '') }}',
                
                isSystolicAlert() { return this.systolic !== '' && (Number(this.systolic) > 140 || Number(this.systolic) < 90); },
                isDiastolicAlert() { return this.diastolic !== '' && (Number(this.diastolic) > 90 || Number(this.diastolic) < 60); },
                isPulseAlert() { return this.pulse !== '' && (Number(this.pulse) > 110 || Number(this.pulse) < 50); },
                isTempAlert() { return this.temp !== '' && (Number(this.temp) > 38.5 || Number(this.temp) < 35); },
                isSpo2Alert() { return this.spo2 !== '' && Number(this.spo2) < 92; },
                hasAnyAlert() { return this.isSystolicAlert() || this.isDiastolicAlert() || this.isPulseAlert() || this.isTempAlert() || this.isSpo2Alert(); }
            }"
            class="space-y-6"
        >
            @csrf

            <!-- Alert Banner -->
            <template x-if="hasAnyAlert()">
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800 flex items-center gap-2 shadow-sm">
                    <span class="text-base">⚠️</span>
                    <span>Emergency / Critical vital signs detected. Form submission is permitted for clinical recording.</span>
                </div>
            </template>

            <!-- Blood Pressure & Pulse -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Systolic BP (mmHg)</label>
                    <input 
                        type="number" 
                        min="0"
                        name="systolic_bp" 
                        x-model="systolic"
                        placeholder="e.g. 120" 
                        :class="isSystolicAlert() ? 'border-rose-400 bg-rose-50/50 text-rose-900 focus:border-rose-500 focus:ring-rose-500' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500'"
                        class="w-full text-sm rounded-xl transition"
                    >
                    <template x-if="isSystolicAlert()">
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Systolic (< 90 or > 140)</span>
                    </template>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Diastolic BP (mmHg)</label>
                    <input 
                        type="number" 
                        min="0"
                        name="diastolic_bp" 
                        x-model="diastolic"
                        placeholder="e.g. 80" 
                        :class="isDiastolicAlert() ? 'border-rose-400 bg-rose-50/50 text-rose-900 focus:border-rose-500 focus:ring-rose-500' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500'"
                        class="w-full text-sm rounded-xl transition"
                    >
                    <template x-if="isDiastolicAlert()">
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Diastolic (< 60 or > 90)</span>
                    </template>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pulse Rate (bpm)</label>
                    <input 
                        type="number" 
                        min="0"
                        name="pulse_rate" 
                        x-model="pulse"
                        placeholder="e.g. 72" 
                        :class="isPulseAlert() ? 'border-amber-400 bg-amber-50/50 text-amber-900 focus:border-amber-500 focus:ring-amber-500' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500'"
                        class="w-full text-sm rounded-xl transition"
                    >
                    <template x-if="isPulseAlert()">
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">⚠️ Pulse (< 50 or > 110)</span>
                    </template>
                </div>
            </div>

            <!-- Temp, SpO2 & Respiratory -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Temperature (°C)</label>
                    <input 
                        type="number" 
                        step="0.1" 
                        min="0"
                        name="temperature" 
                        x-model="temp"
                        placeholder="e.g. 36.6" 
                        :class="isTempAlert() ? 'border-rose-400 bg-rose-50/50 text-rose-900 focus:border-rose-500 focus:ring-rose-500' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500'"
                        class="w-full text-sm rounded-xl transition"
                    >
                    <template x-if="isTempAlert()">
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Temp (< 35 or > 38.5°C)</span>
                    </template>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">SpO2 (%)</label>
                    <input 
                        type="number" 
                        min="0"
                        max="100"
                        name="spo2" 
                        x-model="spo2"
                        placeholder="e.g. 98" 
                        :class="isSpo2Alert() ? 'border-rose-400 bg-rose-50/50 text-rose-900 focus:border-rose-500 focus:ring-rose-500' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500'"
                        class="w-full text-sm rounded-xl transition"
                    >
                    <template x-if="isSpo2Alert()">
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Low SpO2 (< 92%)</span>
                    </template>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Resp. Rate (breaths/min)</label>
                    <input 
                        type="number" 
                        min="0"
                        name="respiratory_rate" 
                        value="{{ old('respiratory_rate', $visit->vitalSign->respiratory_rate ?? '') }}" 
                        placeholder="e.g. 18" 
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 transition"
                    >
                </div>
            </div>

            <!-- Weight & Height -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Weight (kg)</label>
                    <input type="number" step="0.1" min="0" name="weight" value="{{ old('weight', $visit->vitalSign->weight ?? '') }}" placeholder="e.g. 70.5" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Height (cm)</label>
                    <input type="number" step="0.1" min="0" name="height" value="{{ old('height', $visit->vitalSign->height ?? '') }}" placeholder="e.g. 175" class="w-full text-sm rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
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