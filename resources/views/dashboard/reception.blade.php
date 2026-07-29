<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-md border border-slate-200">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-slate-800">{{ $title }}</h1>
            <div class="flex items-center gap-4">
                <span class="text-slate-600 font-medium">እንኳን ደህና መጡ፣ {{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-md font-medium transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
        
        <p class="text-slate-600">ወደ {{ auth()->user()->role }} ክፍል በስኬት ገብተዋል!</p>
    </div>
</body>
</html>