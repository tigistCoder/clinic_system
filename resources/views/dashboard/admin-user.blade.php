<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-900">Users</h2>
                <p class="mt-1 text-sm text-gray-600">Manage clinic users and roles.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
                <aside class="space-y-6">
                    @include('dashboard.partials.admin-sidebar')
                </aside>
                <div class="space-y-6">
                    <div class="bg-white shadow-sm rounded-3xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900">User management</h3>
                        <p class="mt-2 text-sm text-slate-600">This is the placeholder page for managing admin users.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
