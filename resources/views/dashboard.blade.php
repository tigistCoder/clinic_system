@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-2xl font-bold text-slate-800">እንኳን ደህና መጡ፣ {{ auth()->user()->name }}! 👋</h2>
        <p class="text-slate-500 text-sm mt-1">የክሊኒኩ አጠቃላይ መረጃዎች እና የስራ እንቅስቃሴዎች እዚህ ይገኛሉ።</p>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የዛሬ ታካሚዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">24</h3>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">በተጠባባቂ ላይ</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">8</h3>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">የላብራቶሪ ምርመራዎች</span>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">12</h3>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <i data-lucide="flask-conical" class="w-6 h-6"></i>
            </div>
        </div>
    </div>
</div>
@endsection