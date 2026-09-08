@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ openModal: false, openVisitModal: false, activePatient: null }">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Patients Management</h2>
            <p class="text-xs text-slate-500 mt-1">የታካሚዎች ዝርዝር እና አዲስ መመዝገቢያ</p>
        </div>
        
        <!-- Register Button (Opens Dialogue Box) -->
        @if(auth()->user()?->hasRole(['nurse', 'lab', 'lab_tech', 'pharmacy', 'pharmacist']))
        {{-- Hidden for Nurse, Lab Tech and Pharmacists --}}
        @elseif(auth()->user()?->hasRole('doctor'))
        <button type="button" disabled title="Registration restricted for Doctors" class="bg-slate-100 text-slate-400 px-4 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 cursor-not-allowed opacity-60">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Register New Patient
        </button>
        @else
        <button @click="openModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 shadow-sm transition">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Register New Patient
        </button>
        @endif
    </div>

    <!-- Alert Messages -->
    @if(session('success') || session('error') || $errors->any())
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-1">
        @if(session('success'))
            <p class="text-sm text-emerald-700 font-semibold">✓ {{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="text-sm text-rose-700 font-semibold">✕ {{ session('error') }}</p>
        @endif
        @if($errors->any())
            @foreach($errors->all() as $error)
                <p class="text-sm text-rose-700 font-semibold">✕ {{ $error }}</p>
            @endforeach
        @endif
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase">Total Registered Patients</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalPatients }}</h3>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase">Today's Visits</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $todayVisits }}</h3>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="calendar-check" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <form method="GET" action="{{ route('patients.index') }}" class="flex items-center gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Name, Card Number, or Phone..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>
            <button type="submit" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-xl transition">
                Search
            </button>
        </form>
    </div>

    @if(auth()->user()?->hasRole('doctor'))
    <!-- Doctor Assigned Queue Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 bg-amber-50/60 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <span>🩺</span> Your Assigned Doctor Queue ({{ $assignedVisits->count() }} Waiting)
            </h3>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                Active Doctor Queue
            </span>
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 text-xs uppercase font-semibold">
                    <th class="p-4">Card No.</th>
                    <th class="p-4">Patient Name</th>
                    <th class="p-4">Age / Sex</th>
                    <th class="p-4">Urgency Status</th>
                    <th class="p-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($assignedVisits as $visit)
                <tr class="hover:bg-slate-50/50 transition">
                    <td class="p-4 font-bold text-blue-600">{{ $visit->patient->card_number ?? ('CARD-' . $visit->patient_id) }}</td>
                    <td class="p-4 font-semibold text-slate-800">{{ $visit->patient->name ?? $visit->patient->full_name }}</td>
                    <td class="p-4 text-slate-600">{{ $visit->patient->age }} Yrs / {{ ucfirst($visit->patient->gender) }}</td>
                    <td class="p-4">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $visit->urgency_status === 'emergency' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($visit->urgency_status ?? 'normal') }}
                        </span>
                    </td>
                    <td class="p-4 text-right">
                        <a href="{{ route('visits.show', $visit->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                            <i data-lucide="stethoscope" class="w-3.5 h-3.5"></i>
                            Attend Patient
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-slate-400 font-medium">No patients waiting in your queue.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <!-- Patients Table (General Staff / Admin) -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 text-xs uppercase font-semibold">
                    <th class="p-4">Card No.</th>
                    <th class="p-4">Patient Name</th>
                    <th class="p-4">Age / Sex</th>
                    <th class="p-4">Phone</th>
                    <th class="p-4">Registered Date</th>
                    <th class="p-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($patients as $patient)
                <tr class="hover:bg-slate-50/50 transition">
                    <td class="p-4 font-bold text-blue-600">{{ $patient->card_number ?? $patient->id }}</td>
                    <td class="p-4 font-semibold text-slate-800">{{ $patient->full_name }}</td>
                    <td class="p-4 text-slate-600">{{ $patient->age }} Yrs / {{ ucfirst($patient->gender) }}</td>
                    <td class="p-4 text-slate-600">{{ $patient->phone_number }}</td>
                    <td class="p-4 text-slate-400 text-xs">{{ $patient->created_at->format('Y-m-d') }}</td>
                    <td class="p-4 text-right space-x-2">
                        
                        <!-- 1. QUICK VISIT BUTTON (POP-UP) -->
                        @if(auth()->user()?->hasRole(['doctor', 'nurse', 'lab', 'lab_tech', 'pharmacy', 'pharmacist']))
                        {{-- Hidden for Doctor, Nurse, Lab Tech & Pharmacist --}}
                        @else
                        <button 
                            @click="activePatient = {{ json_encode(['id' => $patient->id, 'card_number' => $patient->card_number ?? $patient->id, 'full_name' => $patient->full_name]) }}; openVisitModal = true" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-lg transition"
                        >
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            Visit
                        </button>
                        @endif

                        <!-- 2. DETAIL BUTTON -->
                        <a href="{{ route('patients.show', $patient) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg transition">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            Detail
                        </a>

                        @if(!auth()->user()?->hasRole(['doctor', 'nurse', 'lab', 'lab_tech', 'pharmacy', 'pharmacist']))
                        <!-- 3. EDIT BUTTON -->
                        <a href="{{ route('patients.edit', $patient) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg transition">
                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                            Edit
                        </a>

                        <!-- 4. DELETE FORM -->
                        <form action="{{ route('patients.destroy', $patient) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg transition" onclick="return confirm('Are you sure you want to delete this patient?');">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                Delete
                            </button>
                        </form>
                        @endif

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-8 text-center text-slate-400">ምንም የተመዘገበ ታካሚ አልተገኘም።</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-100">
            {{ $patients->links() }}
        </div>
    </div>
    @endif

    <!-- DIALOGUE BOX / MODAL (FOR NEW PATIENT) -->
    @if(!auth()->user()?->hasRole(['nurse', 'doctor', 'lab', 'lab_tech', 'pharmacy', 'pharmacist']))
    <div x-show="openModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative space-y-4" @click.away="openModal = false">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-bold text-slate-800">Register New Patient</h3>
                <button @click="openModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('patients.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" pattern="[A-Za-z\s]+" title="Patient name must contain letters only." placeholder="e.g. Abebe Kebede" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('name') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Age (Years)</label>
                        <input type="number" name="age" value="{{ old('age') }}" min="0" max="120" maxlength="3" placeholder="0 - 120" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @error('age') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Gender</label>
                        <select name="gender" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" pattern="[0-9]{9,15}" title="Phone number must contain 9 to 15 digits." placeholder="e.g. 0911123456" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('phone') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" pattern="[A-Za-z\s,]+" title="Address must contain letters only, numbers are not allowed." minlength="2" maxlength="255" placeholder="e.g. Kebele, Assosa" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('address') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="openModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700">Save Patient</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- QUICK VISIT POP-UP MODAL -->
    @if(!auth()->user()?->hasRole(['nurse', 'doctor', 'pharmacy', 'pharmacist']))
    <div x-show="openVisitModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative space-y-4" @click.away="openVisitModal = false">
            
            <div class="flex justify-between items-center border-b pb-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Quick Visit Record</h3>
                    <p class="text-xs text-blue-600 font-semibold" x-text="activePatient ? activePatient.full_name + ' (' + (activePatient.card_number || 'ID: ' + activePatient.id) + ')' : ''"></p>
                </div>
                <button @click="openVisitModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form x-bind:action="'{{ route('visits.store', ['patient' => '__patient__']) }}'.replace('__patient__', activePatient ? activePatient.id : '')" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Visit Priority</label>
                    <select name="urgency_status" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="normal">Normal</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                    A new visit record will open so the nurse, doctor, and lab team can continue the workflow in one place.
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="openVisitModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700">Start Visit</button>
                </div>
            </form>

        </div>
    </div>
    @endif

</div>

<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection