<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthCenter Portal</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- 🚀 1. Alpine.js CDN (ለ Pop-up Modal መስሪያ) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- 🚀 2. Alpine x-cloak CSS (ለአላስፈላጊ Flash/Blink መቀነሻ) -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 font-sans flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-100/80 border-r border-slate-200 flex flex-col justify-between p-4 flex-shrink-0">
        <div>
            <!-- Logo -->
            <div class="mb-8 px-2">
                <h1 class="text-xl font-bold text-blue-900">HealthCenter</h1>
                <p class="text-xs text-slate-500 font-medium">Assosa Clinic Portal</p>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-semibold rounded-lg bg-blue-50 text-blue-700 border-r-4 border-blue-600">
                    <i data-lucide="layout-grid" class="w-5 h-5"></i>
                    Dashboard
                </a>

                @if(in_array(auth()->user()->role, ['admin', 'reception', 'doctor', 'nurse']))
                <a href="{{ route('patients.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 rounded-lg hover:bg-slate-200/60">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    Patients
                </a>
                @endif

                @if(in_array(auth()->user()->role, ['admin', 'reception']))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 rounded-lg hover:bg-slate-200/60">
                    <i data-lucide="calendar" class="w-5 h-5"></i>
                    user
                </a>
                @endif

                @if(in_array(auth()->user()->role, ['admin', 'doctor', 'nurse', 'lab']))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 rounded-lg hover:bg-slate-200/60">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                    Medical Records
                </a>
                @endif

                @if(auth()->user()->role === 'admin')
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 rounded-lg hover:bg-slate-200/60">
                    <i data-lucide="user-cog" class="w-5 h-5"></i>
                    Staff
                </a>
                @endif

                @if(in_array(auth()->user()->role, ['admin', 'pharmacy']))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 rounded-lg hover:bg-slate-200/60">
                    <i data-lucide="box" class="w-5 h-5"></i>
                    Inventory / Pharmacy
                </a>
                @endif
            </nav>
        </div>

        <!-- Bottom Options -->
        <div class="space-y-4">
            @if(in_array(auth()->user()->role, ['admin', 'reception']))
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg shadow-sm transition text-sm flex items-center justify-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                New Appointment
            </button>
            @endif

            <hr class="border-slate-200">

            <div class="space-y-1">
                <!-- Logout Form -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col overflow-y-auto">
        <!-- Header -->
        <header class="h-20 bg-white border-b border-slate-100 flex items-center justify-between px-8 sticky top-0 z-10">
            <div class="flex items-center gap-3 w-1/2">
                <div class="relative w-full">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Search patients, events, or staff..." 
                           class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
                <button class="px-4 py-2 bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 text-xs font-semibold rounded-lg transition whitespace-nowrap">
                    Emergency
                </button>
            </div>

            <div class="flex items-center gap-5">
                <div class="text-right">
                    <h4 class="text-sm font-bold text-slate-800 leading-none">{{ auth()->user()->name }}</h4>
                    <span class="text-xs text-slate-500 capitalize">{{ auth()->user()->role }}</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-8">
            @yield('content')
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>