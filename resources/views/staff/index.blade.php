@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    createForm: { name: '', email: '', password: '', role: 'doctor', status: 'active' },
    editForm: { id: null, name: '', email: '', password: '', role: 'doctor', status: 'active' },
    openCreateModal() {
        this.createModalOpen = true;
        this.createForm = { name: '', email: '', password: '', role: 'doctor', status: 'active' };
    },
    closeCreateModal() {
        this.createModalOpen = false;
    },
    openEditModal(member) {
        this.editForm = {
            id: member.id,
            name: member.name || '',
            email: member.email || '',
            password: '',
            role: member.role || 'doctor',
            status: member.status || 'active'
        };
        this.editModalOpen = true;
    },
    closeEditModal() {
        this.editModalOpen = false;
        this.editForm = { id: null, name: '', email: '', password: '', role: 'doctor', status: 'active' };
    }
}">
    @if(session('success') || session('error') || $errors->any())
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-1">
            @if(session('success'))
                <p class="text-sm font-semibold text-emerald-700">✓ {{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="text-sm font-semibold text-rose-700">✕ {{ session('error') }}</p>
            @endif
            @if($errors->any())
                @foreach($errors->all() as $error)
                    <p class="text-sm font-semibold text-rose-700">✕ {{ $error }}</p>
                @endforeach
            @endif
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Staff Management</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Staff</h1>
                <p class="mt-1 text-sm text-slate-500">Keep your clinic team organized with searchable staff records and quick status views.</p>
            </div>

            <div class="flex w-full max-w-2xl flex-col gap-2 sm:flex-row sm:items-end">
                <form method="GET" action="{{ route('staff.index') }}" class="flex w-full items-center gap-2">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="staff-search" type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or role" class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Search</button>
                </form>
                <button type="button" @click="openCreateModal()" class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">+ Add New Staff</button>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Staff</p>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</h3>
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">All</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Active</p>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-2xl font-bold text-slate-900">{{ $stats['active'] }}</h3>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">On duty</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Inactive</p>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-2xl font-bold text-slate-900">{{ $stats['inactive'] }}</h3>
                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Away</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Active Today</p>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-2xl font-bold text-slate-900">{{ $stats['active_today'] ?? $stats['active'] }}</h3>
                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">Today</span>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('staff.index') }}" class="rounded-full bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white">All Staff</a>
                <a href="{{ route('staff.index', ['role' => 'doctor']) }}" class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">Doctors</a>
                <a href="{{ route('staff.index', ['role' => 'nurse']) }}" class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">Nurses</a>
                <a href="{{ route('staff.index', ['role' => 'admin']) }}" class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">Admin</a>
            </div>

            <div class="flex items-center gap-2">
                <label for="sort-by" class="text-sm font-medium text-slate-600">Sort by</label>
                <select id="sort-by" onchange="window.location.href='{{ route('staff.index') }}?'+new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), sort_by: this.value})" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Name</option>
                    <option value="status" {{ request('sort_by') === 'status' ? 'selected' : '' }}>Status</option>
                    <option value="role" {{ request('sort_by') === 'role' ? 'selected' : '' }}>Role</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Staff Member</th>
                        <th class="px-4 py-3 font-semibold">Role & Dept</th>
                        <th class="px-4 py-3 font-semibold">Contact Info</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($staff as $member)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                        {{ strtoupper(substr($member->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $member->name }}</p>
                                        <p class="text-xs text-slate-500">ID: {{ $member->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold capitalize text-slate-800">{{ $member->role }}</p>
                                <p class="text-xs text-slate-500">Clinic Team</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-slate-700">{{ $member->email }}</p>
                                <p class="text-xs text-slate-500">@ {{ $member->username }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($member->status === 'active')
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200" @click="openEditModal({{ json_encode($member) }})">Edit</button>
                                    <form action="{{ route('staff.destroy', $member) }}" method="POST" onsubmit="return confirm('Delete this staff member?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-100">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No staff found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">Showing {{ $staff->firstItem() ?? 0 }} to {{ $staff->lastItem() ?? 0 }} of {{ $staff->total() }} staff</p>
            <div class="{{ $staff->hasPages() ? '' : 'hidden' }}">
                {{ $staff->links() }}
            </div>
        </div>
    </div>

    <!-- CREATE STAFF MODAL -->
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Add New Staff</h2>
                    <p class="mt-1 text-sm text-slate-500">Create a new clinic account and assign access.</p>
                </div>
                <button type="button" @click="closeCreateModal()" class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">Close</button>
            </div>

            <form action="{{ route('staff.store') }}" method="POST" class="mt-6 grid gap-4 md:grid-cols-2">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Full Name</label>
                    <input type="text" name="name" x-model="createForm.name" pattern="[A-Za-z\s]+" title="Staff name must contain letters only" minlength="3" maxlength="255" placeholder="e.g. Dr Abebe Kebede" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" x-model="createForm.email" placeholder="example@domain.com" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" x-model="createForm.password" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                    <select name="role" x-model="createForm.role" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="doctor">Doctor</option>
                        <option value="nurse">Nurse</option>
                        <option value="admin">Admin</option>
                        <option value="reception">Reception</option>
                        <option value="lab">Lab</option>
                        <option value="pharmacy">Pharmacy</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                    <select name="status" x-model="createForm.status" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                    <button type="button" @click="closeCreateModal()" class="rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Save Staff</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT STAFF MODAL -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Edit Staff</h2>
                    <p class="mt-1 text-sm text-slate-500">Update staff details and permissions.</p>
                </div>
                <button type="button" @click="closeEditModal()" class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">Close</button>
            </div>

            <form :action="'{{ url('/staff') }}/' + editForm.id" method="POST" class="mt-6 grid gap-4 md:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Full Name</label>
                    <input type="text" name="name" x-model="editForm.name" pattern="[A-Za-z\s]+" title="Staff name must contain letters only" minlength="3" maxlength="255" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" x-model="editForm.email" placeholder="example@domain.com" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" x-model="editForm.password" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" placeholder="Leave blank to keep existing password">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                    <select name="role" x-model="editForm.role" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="doctor">Doctor</option>
                        <option value="nurse">Nurse</option>
                        <option value="admin">Admin</option>
                        <option value="reception">Reception</option>
                        <option value="lab">Lab</option>
                        <option value="pharmacy">Pharmacy</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                    <select name="status" x-model="editForm.status" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                    <button type="button" @click="closeEditModal()" class="rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Update Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection