@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    quickMedicineModalOpen: false,
    medicinesList: {{ json_encode($medicinesList ?? []) }},
    createForm: { medicine_id: '', batch_number: '', quantity_purchased: 50, price_per_unit: '', purchase_date: '{{ date('Y-m-d') }}', expiry_date: '' },
    quickForm: { name: '', category: 'Antibiotics', generic_name: '', price_per_unit: '' },
    editForm: { id: null, name: '', category: 'Antibiotics', generic_name: '', price_per_unit: '' },
    openCreateModal() {
        this.createModalOpen = true;
    },
    closeCreateModal() {
        this.createModalOpen = false;
    },
    openEditModal(item) {
        this.editForm = {
            id: item.id,
            name: item.name || '',
            category: item.category || 'Antibiotics',
            generic_name: item.generic_name || '',
            price_per_unit: item.price_per_unit || ''
        };
        this.editModalOpen = true;
    },
    closeEditModal() {
        this.editModalOpen = false;
    },
    onMedicineChange() {
        const selected = this.medicinesList.find(m => m.id == this.createForm.medicine_id);
        if (selected) {
            this.createForm.price_per_unit = selected.price_per_unit || '';
        }
    },
    async submitQuickMedicine() {
        if (!this.quickForm.name || !this.quickForm.price_per_unit || !this.quickForm.category) {
            alert('Please provide medicine name, category, and price per unit.');
            return;
        }
        try {
            const response = await fetch('{{ route('medicines.quick_store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.quickForm)
            });
            const result = await response.json();
            if (result.success) {
                this.medicinesList.push(result.medicine);
                this.createForm.medicine_id = result.medicine.id;
                this.createForm.price_per_unit = result.medicine.price_per_unit;
                this.quickForm = { name: '', category: 'Antibiotics', generic_name: '', price_per_unit: '' };
                this.quickMedicineModalOpen = false;
                alert('✓ ' + result.message);
            } else {
                alert('Error: ' + (result.message || 'Validation failed'));
            }
        } catch (err) {
            alert('Network error: ' + err.message);
        }
    }
}">
    <!-- Alert Messages -->
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

    <!-- Header Section -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Pharmacy & FEFO Batch Inventory</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Inventory Overview</h1>
                <p class="mt-1 text-sm text-slate-500">Real-time batch tracking with automatic FEFO stock deduction and expired item exclusion.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form method="GET" action="{{ route('medicines.index') }}" class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by item or generic name..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="submit" class="rounded-2xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900">Search</button>
                </form>

                <button type="button" @click="openCreateModal()" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    + Add Stock Batch
                </button>
            </div>
        </div>
    </div>

    <!-- Top Summary Metric Cards -->
    <div class="grid gap-4 md:grid-cols-4">
        <!-- 1. Total SKU Count -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total SKU Count</p>
                <div class="rounded-xl bg-blue-50 p-2 text-blue-600">
                    <i data-lucide="boxes" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ $stats['total_sku'] }}</h3>
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Live Items</span>
            </div>
        </div>

        <!-- 2. Low Stock Alerts -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Low Stock Alerts</p>
                <div class="rounded-xl bg-rose-50 p-2 text-rose-600">
                    <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ $stats['low_stock'] }}</h3>
                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">Action Required</span>
            </div>
        </div>

        <!-- 3. Pending Orders -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pending Orders</p>
                <div class="rounded-xl bg-amber-50 p-2 text-amber-600">
                    <i data-lucide="clock" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ $stats['pending_orders'] }}</h3>
                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Reorder Items</span>
            </div>
        </div>

        <!-- 4. Monthly Sold Medications -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Monthly Sold</p>
                <div class="rounded-xl bg-violet-50 p-2 text-violet-600">
                    <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ number_format($stats['monthly_sold']) }}</h3>
                <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">Units Dispensed</span>
            </div>
        </div>
    </div>

    <!-- Main Stock Inventory Table Card -->
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('medicines.index') }}" class="rounded-full px-3.5 py-1.5 text-xs font-semibold {{ !request('status') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All Items</a>
                <a href="{{ route('medicines.index', ['status' => 'in_stock']) }}" class="rounded-full px-3.5 py-1.5 text-xs font-semibold {{ request('status') === 'in_stock' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">In Stock</a>
                <a href="{{ route('medicines.index', ['status' => 'low_stock']) }}" class="rounded-full px-3.5 py-1.5 text-xs font-semibold {{ request('status') === 'low_stock' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Low Stock</a>
                <a href="{{ route('medicines.index', ['status' => 'out_of_stock']) }}" class="rounded-full px-3.5 py-1.5 text-xs font-semibold {{ request('status') === 'out_of_stock' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Out of Stock</a>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm border-collapse">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Item Details</th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5">Available Stock (Non-Expired)</th>
                        <th class="px-4 py-3.5">Unit Price</th>
                        <th class="px-4 py-3.5">Next Expiry (FEFO)</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($medicines as $med)
                        @php
                            $validStock = $med->valid_stock_quantity;
                            $expiredStock = $med->expired_stock_quantity;
                            $earliestExpiry = $med->earliest_expiry_date;
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 font-bold text-sm">
                                        <i data-lucide="pill" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $med->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $med->generic_name ?? 'Standard Formula' }} • SKU #MED-{{ str_pad($med->id, 3, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $med->category ?? 'General' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-base font-extrabold text-slate-900">{{ number_format($validStock) }}</span>
                                        <span class="text-xs font-semibold text-slate-500">valid units</span>
                                    </div>
                                    @if($expiredStock > 0)
                                        <span class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-bold text-rose-600">
                                            ⚠️ {{ number_format($expiredStock) }} expired units auto-excluded
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-800">
                                {{ number_format($med->price_per_unit, 2) }} ETB
                            </td>
                            <td class="px-4 py-3.5">
                                @if($earliestExpiry)
                                    <span class="text-sm font-medium text-slate-700">{{ \Carbon\Carbon::parse($earliestExpiry)->format('M d, Y') }}</span>
                                    @if(\Carbon\Carbon::parse($earliestExpiry)->diffInDays(now()) <= 30)
                                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Expiring Soon</span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">No active batches</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @if($validStock > 10)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                        In Stock
                                    </span>
                                @elseif($validStock > 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-600 animate-pulse"></span>
                                        Low Stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Out of Stock
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="openEditModal({{ json_encode($med) }})" class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                                        ✏️ Edit
                                    </button>
                                    <form action="{{ route('medicines.destroy', $med) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this medication item and all its batch records?');" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No medication stock items found in inventory.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">Showing {{ $medicines->firstItem() ?? 0 }} to {{ $medicines->lastItem() ?? 0 }} of {{ $medicines->total() }} stock items</p>
            <div class="{{ $medicines->hasPages() ? '' : 'hidden' }}">
                {{ $medicines->links() }}
            </div>
        </div>
    </div>

    <!-- Add Stock Batch Modal -->
    <div x-show="createModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div @click.away="closeCreateModal()" class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">+ Add Stock Batch (FEFO Entry)</h2>
                    <p class="text-xs text-slate-500">Register a new medication batch into inventory with FEFO tracking.</p>
                </div>
                <button type="button" @click="closeCreateModal()" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200">✕</button>
            </div>

            <form action="{{ route('medicines.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf

                <!-- Medicine Selection Field + Shortcut Button -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold uppercase text-slate-600">Select Medicine *</label>
                        <button type="button" @click="quickMedicineModalOpen = true" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline">
                            + Add New Medicine
                        </button>
                    </div>
                    <select name="medicine_id" x-model="createForm.medicine_id" @change="onMedicineChange()" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="">-- Select Medicine from List --</option>
                        <template x-for="item in medicinesList" :key="item.id">
                            <option :value="item.id" x-text="item.name + (item.category ? ' (' + item.category + ')' : (item.generic_name ? ' (' + item.generic_name + ')' : ''))"></option>
                        </template>
                    </select>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Batch Number (Optional - Auto: BATCH-001)</label>
                        <input type="text" name="batch_number" x-model="createForm.batch_number" pattern="[A-Za-z0-9]+" title="Batch number must contain letters and numbers only." placeholder="Leave blank for auto BATCH-001" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Quantity Purchased *</label>
                        <input type="number" name="quantity_purchased" x-model="createForm.quantity_purchased" min="1" max="100000" placeholder="1 - 100,000" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Unit Cost (ETB) *</label>
                        <input type="number" step="0.01" name="unit_cost" x-model="createForm.price_per_unit" min="0.01" placeholder="Min 0.01" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Purchase Date *</label>
                        <input type="date" name="purchase_date" x-model="createForm.purchase_date" max="{{ date('Y-m-d') }}" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Expiry Date *</label>
                        <input type="date" name="expiry_date" x-model="createForm.expiry_date" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="closeCreateModal()" class="rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Register Batch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- QUICK ADD NEW MEDICINE SUB-MODAL -->
    <div x-show="quickMedicineModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4">
        <div @click.away="quickMedicineModalOpen = false" class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">+ Add New Medicine</h3>
                    <p class="text-xs text-slate-500">Quickly register a new medication item to select in stock batch.</p>
                </div>
                <button type="button" @click="quickMedicineModalOpen = false" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200">✕</button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Medicine Name *</label>
                    <input type="text" x-model="quickForm.name" placeholder="e.g. Amoxicillin, Paracetamol" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Category *</label>
                    <select x-model="quickForm.category" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        <option value="Antibiotics">Antibiotics</option>
                        <option value="Painkillers / Analgesics">Painkillers / Analgesics</option>
                        <option value="Vitamins & Supplements">Vitamins & Supplements</option>
                        <option value="Antihypertensives">Antihypertensives</option>
                        <option value="Antidiabetics">Antidiabetics</option>
                        <option value="Antihistamines / Allergy">Antihistamines / Allergy</option>
                        <option value="Antacids / Gastrointestinal">Antacids / Gastrointestinal</option>
                        <option value="Dermatological / Ointments">Dermatological / Ointments</option>
                        <option value="Injections / IV Fluids">Injections / IV Fluids</option>
                        <option value="Syrups & Liquids">Syrups & Liquids</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Generic Name / Strength</label>
                    <input type="text" x-model="quickForm.generic_name" placeholder="e.g. Amoxicillin 500mg" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Default Price / Unit (ETB) *</label>
                    <input type="number" step="0.01" x-model="quickForm.price_per_unit" min="0.01" placeholder="e.g. 25.50" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" @click="quickMedicineModalOpen = false" class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">Cancel</button>
                <button type="button" @click="submitQuickMedicine()" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Create & Select</button>
            </div>
        </div>
    </div>

    <!-- Edit Medication Modal -->
    <div x-show="editModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div @click.away="closeEditModal()" class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Edit Medication Details</h2>
                    <p class="text-xs text-slate-500">Update item name, category, or default unit price.</p>
                </div>
                <button type="button" @click="closeEditModal()" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200">✕</button>
            </div>

            <form :action="'{{ url('/medicines') }}/' + editForm.id" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Medication Name</label>
                    <input type="text" name="name" x-model="editForm.name" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Category *</label>
                        <select name="category" x-model="editForm.category" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                            <option value="Antibiotics">Antibiotics</option>
                            <option value="Painkillers / Analgesics">Painkillers / Analgesics</option>
                            <option value="Vitamins & Supplements">Vitamins & Supplements</option>
                            <option value="Antihypertensives">Antihypertensives</option>
                            <option value="Antidiabetics">Antidiabetics</option>
                            <option value="Antihistamines / Allergy">Antihistamines / Allergy</option>
                            <option value="Antacids / Gastrointestinal">Antacids / Gastrointestinal</option>
                            <option value="Dermatological / Ointments">Dermatological / Ointments</option>
                            <option value="Injections / IV Fluids">Injections / IV Fluids</option>
                            <option value="Syrups & Liquids">Syrups & Liquids</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Price / Unit (ETB)</label>
                        <input type="number" step="0.01" name="price_per_unit" x-model="editForm.price_per_unit" min="0.01" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Generic Name / Strength</label>
                    <input type="text" name="generic_name" x-model="editForm.generic_name" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="closeEditModal()" class="rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Update Details</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
