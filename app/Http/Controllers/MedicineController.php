<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedicineController extends Controller
{
    /**
     * Display the inventory overview page using Purchases / Batches FEFO structure.
     * Eager loads purchases to prevent N+1 query infinite loop / 30s timeout issues.
     */
    public function index(Request $request)
    {
        if (auth()->user()?->hasRole(['doctor', 'nurse'])) {
            abort(403, 'Access to inventory is restricted.');
        }
        // 1. Single query to fetch all medicines with eager-loaded purchases
        $rawMedicines = Medicine::with(['purchases' => function ($q) {
            $q->orderBy('expiry_date', 'asc');
        }])->latest()->get();

        $allMedicines = $rawMedicines;

        // 2. Search filter in memory
        if ($request->filled('search')) {
            $search = mb_strtolower(trim($request->search));
            $allMedicines = $allMedicines->filter(function ($m) use ($search) {
                return str_contains(mb_strtolower($m->name), $search)
                    || str_contains(mb_strtolower($m->generic_name ?? ''), $search)
                    || str_contains(mb_strtolower($m->category ?? ''), $search);
            });
        }

        // 3. Status Filter in memory based on dynamic valid non-expired stock
        if ($request->filled('status')) {
            $status = $request->status;
            $allMedicines = $allMedicines->filter(function ($med) use ($status) {
                $validStock = $med->valid_stock_quantity;
                if ($status === 'in_stock') return $validStock > 10;
                if ($status === 'low_stock') return $validStock > 0 && $validStock <= 10;
                if ($status === 'out_of_stock') return $validStock === 0;
                return true;
            });
        }

        // 4. Manual pagination for filtered collection
        $page = Paginator::resolveCurrentPage() ?: 1;
        $perPage = 10;
        $medicines = new \Illuminate\Pagination\LengthAwarePaginator(
            $allMedicines->forPage($page, $perPage)->values(),
            $allMedicines->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // 5. Metrics Summary (Calculated in-memory with ZERO additional database queries)
        $stats = [
            'total_sku'      => $rawMedicines->count(),
            'low_stock'      => $rawMedicines->filter(fn($m) => $m->valid_stock_quantity > 0 && $m->valid_stock_quantity <= ($m->reorder_level ?? 10))->count(),
            'out_of_stock'   => $rawMedicines->filter(fn($m) => $m->valid_stock_quantity === 0)->count(),
            'pending_orders' => $rawMedicines->filter(fn($m) => $m->valid_stock_quantity <= ($m->reorder_level ?? 10))->count(),
            'monthly_sold'   => (int) Prescription::where('status', 'dispensed')
                                 ->whereMonth('updated_at', now()->month)
                                 ->whereYear('updated_at', now()->year)
                                 ->sum('quantity'),
        ];

        // All medicines list for selection dropdown
        $medicinesList = Medicine::orderBy('name', 'asc')->get(['id', 'name', 'category', 'generic_name', 'price_per_unit']);

        return view('medicines.index', compact('medicines', 'stats', 'medicinesList'));
    }

    /**
     * Store a new stock purchase batch into inventory with strict schema validation.
     */
    public function store(Request $request)
    {
        $unitCostInput = $request->input('unit_cost') ?? $request->input('price_per_unit');
        $request->merge(['unit_cost' => $unitCostInput]);

        $validated = $request->validate([
            'medicine_id'        => ['required_without:name', 'nullable', 'exists:medicines,id'],
            'name'               => ['required_without:medicine_id', 'nullable', 'string', 'max:255'],
            'batch_number'       => ['nullable', 'string', 'alpha_num', 'max:50'],
            'quantity_purchased' => ['required', 'integer', 'min:1', 'max:100000'],
            'expiry_date'        => ['required', 'date', 'after:today'],
            'purchase_date'      => ['required', 'date', 'before_or_equal:today'],
            'unit_cost'          => ['required', 'numeric', 'min:0.01'],
            'category'           => ['nullable', 'string', 'max:100'],
            'generic_name'       => ['nullable', 'string', 'max:255'],
        ], [
            'medicine_id.required_without'   => 'Please select a medicine from the list or register a new one.',
            'medicine_id.exists'             => 'Selected medicine does not exist in inventory.',
            'batch_number.alpha_num'         => 'Batch number must contain letters and numbers only.',
            'quantity_purchased.required'     => 'Quantity purchased is required.',
            'quantity_purchased.min'         => 'Quantity must be at least 1.',
            'quantity_purchased.max'         => 'Quantity cannot exceed 100,000.',
            'expiry_date.required'           => 'Expiry date is required.',
            'expiry_date.after'              => 'Expiry date must be a future date.',
            'purchase_date.required'         => 'Purchase date is required.',
            'purchase_date.before_or_equal'  => 'Purchase date cannot be in the future.',
            'unit_cost.required'              => 'Unit cost must be greater than zero.',
            'unit_cost.min'                  => 'Unit cost must be greater than zero.',
        ]);

        try {
            DB::beginTransaction();

            if (!empty($validated['medicine_id'])) {
                $medicine = Medicine::findOrFail($validated['medicine_id']);
                $medicine->update(['price_per_unit' => $validated['unit_cost']]);
            } else {
                $medicineName = trim($validated['name']);
                $medicine = Medicine::firstOrCreate(
                    ['name' => $medicineName],
                    [
                        'category'       => $validated['category'] ?? 'General',
                        'generic_name'   => $validated['generic_name'] ?? $medicineName,
                        'price_per_unit' => $validated['unit_cost'],
                        'stock_quantity' => 0,
                        'reorder_level'  => 10,
                        'expiry_date'    => $validated['expiry_date'],
                    ]
                );
            }

            // Sequential Auto-Generation (BATCH-001 Format) if batch_number is empty
            if (empty(trim($validated['batch_number'] ?? ''))) {
                $lastBatch = Purchase::where('batch_number', 'LIKE', 'BATCH-%')
                    ->orderByRaw('CAST(SUBSTRING(batch_number, 7) AS UNSIGNED) DESC')
                    ->first();

                if ($lastBatch) {
                    $lastNum = (int) filter_var($lastBatch->batch_number, FILTER_SANITIZE_NUMBER_INT);
                    $nextNum = $lastNum + 1;
                } else {
                    $nextNum = 1;
                }

                $batchNumber = 'BATCH-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            } else {
                $batchNumber = trim($validated['batch_number']);
            }

            $qty = (int) $validated['quantity_purchased'];

            // Create new Batch / Purchase record
            $medicine->purchases()->create([
                'batch_number'       => $batchNumber,
                'quantity_purchased' => $qty,
                'quantity_sold'      => 0,
                'remaining_quantity' => $qty,
                'purchase_date'      => $validated['purchase_date'],
                'expiry_date'        => $validated['expiry_date'],
            ]);

            DB::commit();

            return redirect()->route('medicines.index')
                ->with('success', "Stock Batch '{$batchNumber}' ({$qty} units) registered successfully under FEFO inventory!");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Medicine stock batch store failed: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX endpoint for quick medicine registration inside Add Stock modal.
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255|unique:medicines,name',
            'category'       => 'required|string|max:100',
            'generic_name'   => 'nullable|string|max:255',
            'price_per_unit' => 'required|numeric|min:0.01',
        ], [
            'name.required'           => 'Medicine name is required.',
            'name.unique'             => 'This medicine already exists in inventory.',
            'category.required'       => 'Category is required.',
            'price_per_unit.required' => 'Price per unit is required.',
            'price_per_unit.min'      => 'Price must be greater than zero.',
        ]);

        try {
            DB::beginTransaction();

            $medicine = Medicine::create([
                'name'           => trim($validated['name']),
                'category'       => $validated['category'],
                'generic_name'   => $validated['generic_name'] ?? trim($validated['name']),
                'price_per_unit' => $validated['price_per_unit'],
                'stock_quantity' => 0,
                'reorder_level'  => 10,
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => "Medicine '{$medicine->name}' registered successfully!",
                'medicine' => $medicine,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Quick medicine store failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to register medicine: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update medicine item details.
     */
    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'category'       => ['required', 'string', 'max:100'],
            'generic_name'   => ['nullable', 'string', 'max:255'],
            'price_per_unit' => ['required', 'numeric', 'min:0.01'],
        ], [
            'category.required' => 'Category is required.',
        ]);

        try {
            DB::beginTransaction();

            $medicine->update($validated);

            DB::commit();

            return redirect()->route('medicines.index')
                ->with('success', 'Medicine details updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Medicine update failed for ID #{$medicine->id}: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified medicine and its batch history.
     */
    public function destroy(Medicine $medicine)
    {
        try {
            DB::beginTransaction();

            $medicine->delete();

            DB::commit();

            return redirect()->route('medicines.index')
                ->with('success', 'Medication item and batch history removed from inventory.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Medicine delete failed for ID #{$medicine->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }
}
