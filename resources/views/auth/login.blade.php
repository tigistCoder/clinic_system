<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assosa Clinic System - Login</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center font-sans antialiased">
    <div class="w-full max-w-md bg-white p-8 rounded-3xl shadow-xl border border-slate-200 space-y-6">
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-bold text-slate-900">Assosa Clinic System</h1>
            <p class="text-sm text-slate-500">እባክዎ መለያዎን ያስገቡ</p>
        </div>

        @if(session('status'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl font-medium">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-xl space-y-1">
                @foreach($errors->all() as $error)
                    <p>✕ {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    placeholder="Enter email address"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-blue-500 focus:outline-none transition">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Password</label>
                <input id="password" type="password" name="password" required
                    placeholder="Enter password"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-blue-500 focus:outline-none transition">
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl shadow-md transition duration-150">
                Sign In
            </button>
        </form>
    </div>
</body>
</html>