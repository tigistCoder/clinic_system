<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-900">Admin Dashboard</h2>
                <p class="mt-1 text-sm text-gray-600">Overview of clinic activity, patients, appointments, and operations.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="#" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Full Record PDF</a>
                <button class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-medium text-gray-700 rounded-md shadow-sm hover:bg-gray-50">Add New Entry</button>
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
                    <section class="bg-white shadow-sm rounded-3xl border border-gray-200 overflow-hidden">
                        <div class="p-6 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Patient</p>
                                <h1 class="mt-1 text-2xl font-semibold text-gray-900">Eleanor Vance</h1>
                                <p class="mt-1 text-sm text-gray-600">DOB: 12 May 1978 (45y)</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700">Blood Type: O Positive</span>
                                <span class="px-3 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">Allergy: Penicillin</span>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-50 text-slate-700">ID: PT-8829-01</span>
                                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-50 text-slate-700">Status: Active</span>
                            </div>
                        </div>
                    </section>

                    <section class="bg-white shadow-sm rounded-3xl border border-gray-200 overflow-hidden">
                        <div class="p-6 border-b border-gray-200 flex items-center justify-between gap-4">
                            <h2 class="text-lg font-semibold text-gray-900">Clinical Timeline</h2>
                            <div class="inline-flex items-center rounded-md border border-gray-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                <span>All Visits</span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-200">
                            <article class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-slate-500">Oct 24, 2023</p>
                                        <h3 class="mt-2 text-xl font-semibold text-gray-900">Follow-up Cardiology Consult</h3>
                                        <p class="mt-3 text-sm text-slate-600">Mild Mitral Valve Prolapse - Stable</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 px-3 py-1 text-sm font-medium">COMPLETED</span>
                                </div>
                                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-slate-50 p-4">
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Diagnosis</p>
                                        <p class="mt-2 text-sm text-slate-700">Mild Mitral Valve Prolapse - Stable</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 p-4">
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Reason for Visit</p>
                                        <p class="mt-2 text-sm text-slate-700">“Occasional palpitations during exercise.”</p>
                                    </div>
                                </div>
                                <div class="mt-5 flex flex-wrap gap-3 text-sm font-medium">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800">View Notes</a>
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800">Prescription</a>
                                </div>
                            </article>
                            <article class="p-6 bg-slate-50">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-slate-500">Aug 12, 2023</p>
                                        <h3 class="mt-2 text-xl font-semibold text-gray-900">Comprehensive Blood Panel</h3>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-sky-100 text-sky-800 px-3 py-1 text-sm font-medium">ROUTINE</span>
                                </div>
                                <p class="mt-4 text-sm text-slate-600">All metrics within normal ranges except for Vitamin D-25 deficiency (18 ng/mL).</p>
                                <div class="mt-5 flex flex-wrap gap-3">
                                    <a href="#" class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2 text-sm text-slate-700 border border-gray-200 hover:bg-gray-50">Hematology_Report.pdf</a>
                                    <a href="#" class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2 text-sm text-slate-700 border border-gray-200 hover:bg-gray-50">Metabolic_Panel.pdf</a>
                                </div>
                            </article>
                            <article class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-slate-500">Jan 05, 2023</p>
                                        <h3 class="mt-2 text-xl font-semibold text-gray-900">ER Admission - Respiratory Distress</h3>
                                        <p class="mt-3 text-sm text-slate-600">Acute Bronchitis (Bacterial)</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-rose-100 text-rose-800 px-3 py-1 text-sm font-medium">ACUTE</span>
                                </div>
                                <p class="mt-5 text-sm text-slate-600">Administered: Oxygen Therapy, Albuterol</p>
                                <div class="mt-5">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800">View Discharge Summary</a>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section class="rounded-3xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Recent Diagnostics</h2>
                            <a href="#" class="text-sm text-indigo-600 hover:text-indigo-800">View all</a>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="rounded-3xl bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">LAB RESULT</p>
                                        <p class="mt-1 text-sm text-slate-600">Lipid Profile</p>
                                    </div>
                                    <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Nov 15, 2023</span>
                                </div>
                                <div class="mt-4 flex items-center gap-3 text-sm text-slate-700">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 border border-gray-200">Download</span>
                                </div>
                            </div>
                            <div class="rounded-3xl bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">IMAGING</p>
                                        <p class="mt-1 text-sm text-slate-600">Chest X-Ray PA View</p>
                                    </div>
                                    <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Jan 06, 2023</span>
                                </div>
                                <div class="mt-4 flex items-center gap-3 text-sm text-slate-700">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 border border-gray-200">View</span>
                                </div>
                            </div>
                            <div class="rounded-3xl bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">IMAGING</p>
                                        <p class="mt-1 text-sm text-slate-600">Echocardiogram</p>
                                    </div>
                                    <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Oct 24, 2023</span>
                                </div>
                                <div class="mt-4 flex items-center gap-3 text-sm text-slate-700">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 border border-gray-200">View</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Vaccination History</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-3 gap-3 text-sm text-slate-700">
                                <span class="font-semibold">Vaccine</span>
                                <span class="font-semibold">Date</span>
                                <span class="font-semibold">Status</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3 rounded-3xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <span>COVID-19 (Pfizer)</span>
                                <span>Nov 2022</span>
                                <span class="text-emerald-700">Full</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3 rounded-3xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <span>Influenza (Flu)</span>
                                <span>Oct 2023</span>
                                <span class="text-blue-700">Current</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3 rounded-3xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <span>Tetanus Booster</span>
                                <span>Aug 2018</span>
                                <span class="text-amber-700">Due 2028</span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-gray-200 bg-slate-900 text-white shadow-sm overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm uppercase tracking-[0.2em] text-slate-400">Heart Rate Trend</p>
                                    <p class="mt-3 text-3xl font-semibold">72 BPM</p>
                                    <p class="mt-1 text-sm text-slate-300">Last recorded 24h ago</p>
                                </div>
                                <div class="grid grid-cols-6 gap-2">
                                    <span class="h-14 rounded-full bg-slate-700"></span>
                                    <span class="h-20 rounded-full bg-slate-600"></span>
                                    <span class="h-16 rounded-full bg-slate-700"></span>
                                    <span class="h-18 rounded-full bg-slate-600"></span>
                                    <span class="h-14 rounded-full bg-slate-700"></span>
                                    <span class="h-16 rounded-full bg-slate-600"></span>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
