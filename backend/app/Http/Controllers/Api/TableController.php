<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TableController extends Controller
{
    /**
     * Display a listing of tables with active transaction info.
     */
    public function index()
    {
        $tables = Table::orderBy('name')->get()->map(function ($table) {
            // Get active transaction info for occupied/finishing tables
            $activeTransaction = null;
            if (in_array($table->status, ['occupied', 'finishing'])) {
                $activeTransaction = Transaction::where('table_id', $table->id)
                    ->whereIn('status', $table->status === 'occupied' ? ['pending', 'confirmed'] : ['completed'])
                    ->with('customer:id,name,phone')
                    ->latest('updated_at')
                    ->first();
            }

            $tableData = $table->toArray();
            $tableData['active_transaction'] = $activeTransaction ? [
                'id' => $activeTransaction->id,
                'invoice_number' => $activeTransaction->invoice_number,
                'customer_name' => $activeTransaction->customer?->name ?? $activeTransaction->customer_name ?? 'Guest',
                'total' => $activeTransaction->total,
                'status' => $activeTransaction->status,
                'created_at' => $activeTransaction->created_at,
                'updated_at' => $activeTransaction->updated_at,
            ] : null;

            // Count active orders at this table
            $tableData['active_orders_count'] = Transaction::where('table_id', $table->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            return $tableData;
        });

        return response()->json($tables);
    }

    /**
     * Store a newly created table.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:tables,code',
            'status' => 'nullable|in:available,occupied,reserved,finishing',
            'capacity' => 'nullable|integer|min:1|max:20',
        ]);

        // Always slugify the code if provided, otherwise auto-generate
        if (empty($validated['code'])) {
            $validated['code'] = 'table-' . Str::slug($validated['name']);
        } else {
            $validated['code'] = Str::slug($validated['code']);
        }

        $table = Table::create($validated);

        return response()->json($table, 201);
    }

    /**
     * Display the specified table.
     */
    public function show(Table $table)
    {
        return response()->json($table);
    }

    /**
     * Update the specified table.
     */
    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:tables,code,' . $table->id,
            'status' => 'nullable|in:available,occupied,reserved,finishing',
            'capacity' => 'nullable|integer|min:1|max:20',
        ]);

        $table->update($validated);

        return response()->json($table);
    }

    /**
     * Remove the specified table.
     */
    public function destroy(Table $table)
    {
        $table->delete();
        return response()->json(null, 204);
    }

    /**
     * Get table by code (for customer scanning).
     */
    public function findByCode($code)
    {
        $table = Table::where('code', $code)->first();
        
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        // Include active transaction info for customer-side validation
        $activeTransaction = null;
        if (in_array($table->status, ['occupied', 'finishing'])) {
            $activeTransaction = Transaction::where('table_id', $table->id)
                ->whereIn('status', ['pending', 'confirmed', 'completed'])
                ->latest('updated_at')
                ->first();
        }

        return response()->json([
            'table' => $table,
            'active_transaction' => $activeTransaction ? [
                'customer_id' => $activeTransaction->customer_id,
                'status' => $activeTransaction->status,
            ] : null,
        ]);
    }

    /**
     * Generate QR code data for a table.
     */
    public function generateQr(Table $table)
    {
        return response()->json([
            'table' => $table,
            'qr_url' => $table->qr_url,
            'qr_data' => $table->qr_url,
        ]);
    }

    /**
     * Clear table — manual action by staff.
     * Sets table status back to 'available'.
     */
    public function clear(Table $table)
    {
        $table->clearTable();

        return response()->json([
            'message' => 'Meja berhasil dibersihkan',
            'table' => $table->fresh(),
        ]);
    }
}
