@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <a href="{{ route('patients.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 font-semibold">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Patients
    </a>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Edit Patient</h2>
                <p class="text-sm text-slate-500">Update patient information and save changes.</p>
            </div>
        </div>

        <form action="{{ route('patients.update', $patient) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $patient->full_name) }}" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @error('name') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Age</label>
                    <input type="number" name="age" value="{{ old('age', $patient->age) }}" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('age') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Gender</label>
                    <select name="gender" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="male" {{ old('gender', $patient->gender) === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $patient->gender) === 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('gender') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone', $patient->phone_number) }}" required class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @error('phone') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Address</label>
                <input type="text" name="address" value="{{ old('address', $patient->address) }}" class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @error('address') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('patients.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
