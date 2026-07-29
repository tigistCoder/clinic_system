<div class="rounded-3xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Admin Menu</h2>
        <p class="mt-1 text-sm text-slate-500">Quick access to clinic management.</p>
    </div>
    <nav class="space-y-1 p-4">
        <a href="{{ route('admin.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-50' }}">Dashboard</a>
        <a href="{{ route('admin.user') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.user') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-50' }}">Users</a>
        <a href="{{ route('admin.patient') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.patient') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-50' }}">Patients</a>
    </nav>
</div>
