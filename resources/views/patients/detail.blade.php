@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-700">Patient profile</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">MRN {{ $patient->id }}</span>
                </div>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $patient->full_name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $patient->age }} yrs • {{ ucfirst($patient->gender) }} • {{ $patient->phone_number }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(!auth()->user()?->hasRole(['nurse', 'doctor', 'lab', 'lab_tech', 'pharmacy', 'pharmacist']))
                <form action="{{ route('visits.store', $patient) }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Start new visit</button>
                </form>
                @endif
                <a href="{{ route('patients.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Back to patients</a>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-800">Visit summary</h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $patient->visits->count() }} visits</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($patient->visits->sortByDesc('created_at')->take(5) as $visit)
                    <a href="{{ route('visits.show', $visit) }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3 transition hover:border-blue-300 hover:bg-blue-50/50">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $visit->created_at->format('d M Y • H:i') }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ ucfirst($visit->visit_status ?? 'triage') }}</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">Open</span>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No visits recorded yet. Start the first one above.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Latest visit progress</h2>
            @php($latest = $patient->visits->sortByDesc('created_at')->first())
            @if($latest)
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Vitals</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ $latest->vitalSign ? 'Captured' : 'Pending' }}</p>
                    </div>
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Doctor</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ $latest->consultation ? 'Completed' : 'Pending' }}</p>
                    </div>
                    <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Lab / Rx</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ $latest->labRequests->isNotEmpty() || $latest->prescriptions->isNotEmpty() ? 'Active' : 'Pending' }}</p>
                    </div>
                </div>
                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="font-semibold text-slate-800">Next step</p>
                    <p class="mt-1">Open the visit workspace to record vital signs, add the doctor’s notes, and order labs or medication in one place.</p>
                </div>
            @else
                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No visit details available yet.</div>
            @endif
        </div>
    </div>
</div>
@endsection
