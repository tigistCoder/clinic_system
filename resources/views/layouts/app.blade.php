<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clinic System</title>

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-slate-50 font-sans flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between p-4 flex-shrink-0 shadow-sm">

        <div>
            <!-- Logo -->
            <div class="mb-8 px-2">
                <h1 class="text-xl font-bold text-blue-900">
                    HealthCenter
                </h1>

                <p class="text-xs text-slate-500 font-medium mt-1">
                    Assosa Clinic Portal
                </p>
            </div>

            <!-- Navigation -->
            <nav class="space-y-1">

                <!-- Dashboard -->
                <a
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition
                    {{ request()->routeIs('dashboard')
                        ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600 font-semibold'
                        : 'text-slate-600 hover:bg-slate-100' }}"
                >
                    <i data-lucide="layout-grid" class="w-5 h-5"></i>
                    <span>Dashboard</span>
                </a>


                <!-- Patients -->
                @if(in_array(auth()->user()->role, ['admin', 'reception', 'doctor', 'nurse', 'lab', 'lab_tech', 'pharmacy', 'pharmacist']))
                    <a
                        href="{{ route('patients.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition
                        {{ request()->routeIs('patients.*')
                            ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600 font-semibold'
                            : 'text-slate-600 hover:bg-slate-100' }}"
                    >
                        <i data-lucide="users" class="w-5 h-5"></i>
                        <span>Patients</span>
                    </a>
                @endif


                <!-- Staff -->
                @if(auth()->user()->role === 'admin')
                    <a
                        href="{{ route('staff.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition
                        {{ request()->routeIs('staff.*')
                            ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600 font-semibold'
                            : 'text-slate-600 hover:bg-slate-100' }}"
                    >
                        <i data-lucide="user-cog" class="w-5 h-5"></i>
                        <span>Staff Management</span>
                    </a>
                @endif


                <!-- Medical Records -->
                @if(in_array(auth()->user()->role, ['admin', 'doctor', 'nurse', 'lab', 'pharmacy', 'reception']))
                    <a
                        href="{{ route('medical-records.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition
                        {{ request()->routeIs('medical-records.*')
                            ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600 font-semibold'
                            : 'text-slate-600 hover:bg-slate-100' }}"
                    >
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span>Medical Records</span>
                    </a>
                @endif


                <!-- Pharmacy / Inventory -->
                @if(!auth()->user()?->hasRole(['doctor', 'nurse', 'lab', 'lab_tech', 'reception', 'receptionist']))
                    <a
                        href="{{ route('medicines.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition
                        {{ request()->routeIs('medicines.*')
                            ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600 font-semibold'
                            : 'text-slate-600 hover:bg-slate-100' }}"
                    >
                        <i data-lucide="package" class="w-5 h-5"></i>
                        <span>Inventory / Pharmacy</span>
                    </a>
                @endif

            </nav>
        </div>


        <!-- Bottom -->
        <div class="space-y-4">

            <hr class="border-slate-200">

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium
                    text-slate-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition"
                >
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Logout</span>
                </button>
            </form>

        </div>

    </aside>


    <!-- MAIN AREA -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- HEADER -->
        <header
            class="h-20 bg-white border-b border-slate-200
            flex items-center justify-end px-8 flex-shrink-0"
        >

            <div class="flex items-center gap-4">

                <!-- User information -->
                <div class="text-right">

                    <h4 class="text-sm font-bold text-slate-800">
                        {{ auth()->user()->name }}
                    </h4>

                    <span class="text-xs text-slate-500 capitalize">
                        {{ auth()->user()->role }}
                    </span>

                </div>


                <!-- Avatar -->
                <div
                    class="w-10 h-10 rounded-full bg-blue-600 text-white
                    flex items-center justify-center font-bold text-sm shadow-sm"
                >
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>

            </div>

        </header>


        <!-- CONTENT -->
        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">

            @yield('content')

        </main>

    </div>


    <!-- Lucide initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>

</body>
</html>
