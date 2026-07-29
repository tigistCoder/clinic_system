@extends('layouts.app')

@section('content')
<div x-data="{ 
    selectedVisit: {{ $patient->visits->isNotEmpty() ? $patient->visits->sortByDesc('created_at')->first()->toJson() : 'null' }},
    showVitalModal: false,
    showDoctorModal: false,
    showLabModal: false,
    showPrescriptionModal: false
}}" class="p-6 space-y-6">

    <!-- Top Header Section (ለአዝራሮች እና ታካሚ መረጃ) -->
    <div class="flex justify-between items-center bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-slate-800">{{ $patient->full_name }}</h1>
            <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-lg">{{ $patient->mrn }}</span>
            <p class="text-xs text-slate-500">
                {{ $patient->age ?? '23' }} Yrs | {{ ucfirst($patient->gender) }} | 📞 {{ $patient->phone ?? '0944354433' }} | 👤 {{ $patient->created_by ?? 'AA' }}
            </p>
        </div>
        
        <!-- 4 Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- 1. Add Vitals Button -->
            <button @click="showVitalModal = true" type="button" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center gap-1 cursor-pointer">
                + Add Vitals
            </button>

            <!-- 2. Doctor Examine Button -->
            <button @click="showDoctorModal = true" type="button" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center gap-1 cursor-pointer">
                👨‍⚕️ Doctor Examine
            </button>

            <!-- 3. Order Lab Test Button -->
            <button @click="showLabModal = true" type="button" class="px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center gap-1 cursor-pointer">
                🧪 + Order Lab Test
            </button>

            <!-- 4. Prescribe Medicine Button -->
            <button @click="showPrescriptionModal = true" type="button" class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center gap-1 cursor-pointer">
                💊 + Prescribe Medicine
            </button>

            <a href="{{ route('patients.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">
                &lt; Back to Patients
            </a>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Visits History -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm space-y-3">
            <div class="flex justify-between items-center border-b pb-2">
                <h2 class="font-bold text-sm text-slate-700 flex items-center gap-1">📋 Visits History</h2>
                <span class="text-xs text-slate-500 font-semibold">{{ $patient->visits->count() }} Visits</span>
            </div>
            
            <div class="space-y-2 max-h-[600px] overflow-y-auto pr-1">
                @forelse($patient->visits->sortByDesc('created_at') as $visit)
                    <div @click="selectedVisit = {{ $visit->toJson() }}" 
                         :class="selectedVisit && selectedVisit.id === {{ $visit->id }} ? 'border-blue-500 bg-blue-50/50 shadow-sm' : 'border-slate-100 bg-slate-50/50 hover:bg-slate-100/50'"
                         class="p-3.5 rounded-xl border cursor-pointer transition flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                ✍️ {{ $visit->created_at->format('Y-m-d') }}
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase">
                                {{ $visit->status ?? 'triage' }}
                            </span>
                        </div>
                        <button class="px-3 py-1 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition">
                            View
                        </button>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-400">
                        <p class="text-xs font-medium">ለዚህ ታካሚ የተመዘገበ Visit የለም።</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right Column: Visit Details (Tables Display) -->
        <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-6">
            <template x-if="selectedVisit">
                <div class="space-y-6">

                    <!-- Visit Details Header -->
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <h2 class="font-bold text-base text-slate-800">Visit Details</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Recorded Date: <span class="font-bold text-slate-700" x-text="new Date(selectedVisit.created_at).toISOString().split('T')[0]"></span>
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200" x-text="'Status: ' + (selectedVisit.status || 'triage')"></span>
                    </div>

                    <!-- 1️⃣ VITAL SIGNS TABLE -->
                    <div class="bg-slate-50/60 p-4 rounded-xl border border-slate-100 space-y-3">
                        <h3 class="font-bold text-xs text-slate-700 flex items-center gap-1.5 uppercase tracking-wide border-b pb-2">
                            🩺 VITAL SIGNS
                        </h3>

                        <template x-if="selectedVisit.vital_sign">
                            <div class="overflow-x-auto bg-white rounded-lg border border-slate-100">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-slate-100/70 text-slate-600 font-semibold border-b border-slate-200">
                                        <tr>
                                            <th class="py-2.5 px-4 w-1/2">Parameter</th>
                                            <th class="py-2.5 px-4 w-1/2">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Blood Pressure (BP)</td>
                                            <td class="py-2 px-4 font-semibold text-slate-900" x-text="(selectedVisit.vital_sign.systolic_bp || '-') + ' / ' + (selectedVisit.vital_sign.diastolic_bp || '-') + ' mmHg'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Pulse Rate</td>
                                            <td class="py-2 px-4 font-semibold text-slate-900" x-text="(selectedVisit.vital_sign.pulse_rate || '-') + ' bpm'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Temperature</td>
                                            <td class="py-2 px-4 font-semibold text-slate-900" x-text="(selectedVisit.vital_sign.temperature || '-') + ' °C'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">SpO2 (Oxygen)</td>
                                            <td class="py-2 px-4 font-semibold text-slate-900" x-text="(selectedVisit.vital_sign.spo2 || '-') + ' %'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Respiratory Rate</td>
                                            <td class="py-2 px-4 font-semibold text-slate-900" x-text="(selectedVisit.vital_sign.respiratory_rate || '-') + ' bpm'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Weight & Height</td>
                                            <td class="py-2 px-4 font-semibold text-slate-900" x-text="(selectedVisit.vital_sign.weight || '-') + ' kg | ' + (selectedVisit.vital_sign.height || '-') + ' cm'"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="!selectedVisit.vital_sign">
                            <div class="bg-white border border-dashed border-slate-300 rounded-lg p-3 text-center">
                                <p class="text-xs text-slate-400 font-medium">ለዚህ Visit እስካሁን የተመዘገበ Vital Sign የለም።</p>
                            </div>
                        </template>
                    </div>

                    <!-- 2️⃣ CONSULTATION & DIAGNOSIS TABLE -->
                    <div class="bg-slate-50/60 p-4 rounded-xl border border-slate-100 space-y-3">
                        <h3 class="font-bold text-xs text-slate-700 flex items-center gap-1.5 uppercase tracking-wide border-b pb-2">
                            📝 CONSULTATION & DIAGNOSIS
                        </h3>

                        <template x-if="selectedVisit.consultation">
                            <div class="overflow-x-auto bg-white rounded-lg border border-slate-100">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-slate-100/70 text-slate-600 font-semibold border-b border-slate-200">
                                        <tr>
                                            <th class="py-2.5 px-4 w-1/3">Clinical Record</th>
                                            <th class="py-2.5 px-4 w-2/3">Details / Findings</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Chief Complaint</td>
                                            <td class="py-2 px-4 font-semibold text-slate-900" x-text="selectedVisit.consultation.chief_complaint || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">History of Present Illness</td>
                                            <td class="py-2 px-4 text-slate-800" x-text="selectedVisit.consultation.history_present_illness || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Physical Examination</td>
                                            <td class="py-2 px-4 text-slate-800" x-text="selectedVisit.consultation.physical_exam || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Provisional Diagnosis</td>
                                            <td class="py-2 px-4 font-bold text-blue-600" x-text="selectedVisit.consultation.provisional_dx || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 px-4 font-medium">Final Diagnosis</td>
                                            <td class="py-2 px-4 font-bold text-emerald-600" x-text="selectedVisit.consultation.final_dx || 'Pending'"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="!selectedVisit.consultation">
                            <div class="bg-white border border-dashed border-slate-300 rounded-lg p-3 text-center">
                                <p class="text-xs text-slate-400 font-medium">ለዚህ Visit እስካሁን የተደረገ የዶክተር ምርመራ የለም።</p>
                            </div>
                        </template>
                    </div>

                    <!-- 3️⃣ LAB ORDERS TABLE -->
                    <div class="bg-slate-50/60 p-4 rounded-xl border border-slate-100 space-y-3">
                        <h3 class="font-bold text-xs text-slate-700 flex items-center gap-1.5 uppercase tracking-wide border-b pb-2">
                            🧪 LAB ORDERS & RESULTS
                        </h3>

                        <template x-if="selectedVisit.lab_requests && selectedVisit.lab_requests.length > 0">
                            <div class="overflow-x-auto bg-white rounded-lg border border-slate-100">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-slate-100/70 text-slate-600 font-semibold border-b border-slate-200">
                                        <tr>
                                            <th class="py-2.5 px-3">Test Name</th>
                                            <th class="py-2.5 px-3">Price</th>
                                            <th class="py-2.5 px-3">Payment</th>
                                            <th class="py-2.5 px-3">Result</th>
                                            <th class="py-2.5 px-3 text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="lab in selectedVisit.lab_requests" :key="lab.id">
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-2.5 px-3 font-semibold text-slate-800" x-text="lab.test_name"></td>
                                                <td class="py-2.5 px-3 font-mono text-slate-700" x-text="parseFloat(lab.price || 0).toFixed(2) + ' ETB'"></td>
                                                <td class="py-2.5 px-3">
                                                    <span :class="lab.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" x-text="lab.payment_status || 'unpaid'"></span>
                                                </td>
                                                <td class="py-2.5 px-3 font-mono text-slate-800" x-text="lab.result || 'Pending'"></td>
                                                <td class="py-2.5 px-3 text-right">
                                                    <span :class="lab.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800'" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" x-text="lab.status || 'requested'"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="!selectedVisit.lab_requests || selectedVisit.lab_requests.length === 0">
                            <div class="bg-white border border-dashed border-slate-300 rounded-lg p-3 text-center">
                                <p class="text-xs text-slate-400 font-medium">ለዚህ Visit እስካሁን የታዘዘ የላብራቶሪ ምርመራ የለም።</p>
                            </div>
                        </template>
                    </div>

                    <!-- 4️⃣ PRESCRIBED MEDICATIONS TABLE -->
                    <div class="bg-slate-50/60 p-4 rounded-xl border border-slate-100 space-y-3">
                        <h3 class="font-bold text-xs text-slate-700 flex items-center gap-1.5 uppercase tracking-wide border-b pb-2">
                            💊 PRESCRIBED MEDICATIONS
                        </h3>

                        <template x-if="selectedVisit.prescriptions && selectedVisit.prescriptions.length > 0">
                            <div class="overflow-x-auto bg-white rounded-lg border border-slate-100">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-slate-100/70 text-slate-600 font-semibold border-b border-slate-200">
                                        <tr>
                                            <th class="py-2.5 px-3">Medicine Name</th>
                                            <th class="py-2.5 px-3">Dosage</th>
                                            <th class="py-2.5 px-3">Frequency</th>
                                            <th class="py-2.5 px-3">Duration</th>
                                            <th class="py-2.5 px-3 text-right">Instructions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="item in selectedVisit.prescriptions" :key="item.id">
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-2.5 px-3 font-semibold text-slate-800" x-text="item.medicine_name"></td>
                                                <td class="py-2.5 px-3 text-slate-700" x-text="item.dosage || '-'"></td>
                                                <td class="py-2.5 px-3 text-slate-700" x-text="item.frequency || '-'"></td>
                                                <td class="py-2.5 px-3 text-slate-700" x-text="item.duration || '-'"></td>
                                                <td class="py-2.5 px-3 text-right text-slate-500 italic" x-text="item.instructions || 'N/A'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="!selectedVisit.prescriptions || selectedVisit.prescriptions.length === 0">
                            <div class="bg-white border border-dashed border-slate-300 rounded-lg p-3 text-center">
                                <p class="text-xs text-slate-400 font-medium">ለዚህ Visit እስካሁን የታዘዘ መድኃኒት የለም።</p>
                            </div>
                        </template>
                    </div>

                </div>
            </template>

            <template x-if="!selectedVisit">
                <div class="text-center py-12 text-slate-400 border border-dashed border-slate-200 rounded-xl">
                    <p class="text-xs font-medium">እባክዎን መረጃውን ለማየት ከግራ በኩል Visit ይምረጡ።</p>
                </div>
            </template>
        </div>

    </div>

    <!-- ================= 4 POPUP MODALS SECTION ================= -->

    <!-- 1️⃣ VITAL SIGNS POPUP MODAL -->
    <div x-show="showVitalModal" x-cloak x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="showVitalModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-6 relative border border-slate-100">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="font-bold text-lg text-slate-900">🩺 Vital Signs Recording Form</h3>
                <button @click="showVitalModal = false" type="button" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">&times;</button>
            </div>

            <form :action="selectedVisit ? `{{ route('vitals.store', ':id') }}`.replace(':id', selectedVisit.id) : '#'" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Systolic BP (mmHg)</label>
                        <input type="number" name="systolic_bp" :value="selectedVisit?.vital_sign?.systolic_bp || ''" placeholder="120" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Diastolic BP (mmHg)</label>
                        <input type="number" name="diastolic_bp" :value="selectedVisit?.vital_sign?.diastolic_bp || ''" placeholder="80" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pulse Rate (bpm)</label>
                        <input type="number" name="pulse_rate" :value="selectedVisit?.vital_sign?.pulse_rate || ''" placeholder="72" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Temperature (°C)</label>
                        <input type="number" step="0.1" name="temperature" :value="selectedVisit?.vital_sign?.temperature || ''" placeholder="36.6" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">SpO2 (%)</label>
                        <input type="number" name="spo2" :value="selectedVisit?.vital_sign?.spo2 || ''" placeholder="98" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Resp. Rate (bpm)</label>
                        <input type="number" name="respiratory_rate" :value="selectedVisit?.vital_sign?.respiratory_rate || ''" placeholder="18" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" :value="selectedVisit?.vital_sign?.weight || ''" placeholder="70" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Height (cm)</label>
                        <input type="number" step="0.1" name="height" :value="selectedVisit?.vital_sign?.height || ''" placeholder="175" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button @click="showVitalModal = false" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-xs transition shadow-sm">💾 Save Vitals</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2️⃣ DOCTOR CONSULTATION POPUP MODAL -->
    <div x-show="showDoctorModal" x-cloak x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="showDoctorModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-5 relative border border-slate-100">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="font-bold text-lg text-slate-900">👨‍⚕️ Doctor Examination Form</h3>
                <button @click="showDoctorModal = false" type="button" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">&times;</button>
            </div>

            <form :action="selectedVisit ? `{{ route('consultations.store', ':id') }}`.replace(':id', selectedVisit.id) : '#'" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Chief Complaint <span class="text-red-500">*</span></label>
                    <textarea name="chief_complaint" rows="2" placeholder="e.g. Fever, Headache..." class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 p-2.5" required x-text="selectedVisit?.consultation?.chief_complaint || ''"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">History of Present Illness</label>
                        <textarea name="history_present_illness" rows="3" placeholder="Progression..." class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 p-2.5" x-text="selectedVisit?.consultation?.history_present_illness || ''"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Physical Examination</label>
                        <textarea name="physical_exam" rows="3" placeholder="Findings..." class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 p-2.5" x-text="selectedVisit?.consultation?.physical_exam || ''"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Provisional Diagnosis <span class="text-red-500">*</span></label>
                        <input type="text" name="provisional_dx" :value="selectedVisit?.consultation?.provisional_dx || ''" placeholder="e.g. Malaria, Typhoid" class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 p-2.5" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Final Diagnosis</label>
                        <input type="text" name="final_dx" :value="selectedVisit?.consultation?.final_dx || ''" placeholder="Final findings..." class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 p-2.5">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button @click="showDoctorModal = false" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition shadow-sm">💾 Save Consultation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3️⃣ LAB REQUEST POPUP MODAL -->
    <div x-show="showLabModal" x-cloak x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="showLabModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 relative border border-slate-100">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="font-bold text-lg text-slate-900">🧪 Order Laboratory Test</h3>
                <button @click="showLabModal = false" type="button" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">&times;</button>
            </div>

            <form :action="selectedVisit ? `{{ route('lab_requests.store', ':id') }}`.replace(':id', selectedVisit.id) : '#'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Test Name <span class="text-red-500">*</span></label>
                    <input type="text" name="test_name" placeholder="e.g. CBC, Stool Test" class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500 p-2.5" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Price (ETB) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="price" placeholder="150.00" class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500 p-2.5" required>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button @click="showLabModal = false" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl text-xs transition shadow-sm">💾 Send Lab Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4️⃣ PRESCRIPTION POPUP MODAL -->
    <div x-show="showPrescriptionModal" x-cloak x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.away="showPrescriptionModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 relative border border-slate-100">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="font-bold text-lg text-slate-900">💊 Prescribe Medication</h3>
                <button @click="showPrescriptionModal = false" type="button" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">&times;</button>
            </div>

            <form :action="selectedVisit ? `{{ route('prescriptions.store', ':id') }}`.replace(':id', selectedVisit.id) : '#'" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Medicine Name <span class="text-red-500">*</span></label>
                    <input type="text" name="medicine_name" placeholder="e.g. Paracetamol 500mg, Amoxicillin" class="w-full text-xs rounded-xl border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-2.5" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Dosage</label>
                        <input type="text" name="dosage" placeholder="e.g. 1 Tablet" class="w-full text-xs rounded-xl border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Frequency</label>
                        <input type="text" name="frequency" placeholder="e.g. 3x Daily" class="w-full text-xs rounded-xl border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Duration</label>
                        <input type="text" name="duration" placeholder="e.g. 5 Days" class="w-full text-xs rounded-xl border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Special Instructions</label>
                    <textarea name="instructions" rows="2" placeholder="e.g. Take after meals..." class="w-full text-xs rounded-xl border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-2.5"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button @click="showPrescriptionModal = false" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-xl text-xs transition shadow-sm">💾 Save Prescription</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection