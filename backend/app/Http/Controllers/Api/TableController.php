<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TableController extends Controller
{
    /**
     * Display a listing of tables.
     */
    public function index()
    {
        $tables = Table::orderBy('name')->get();
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
            'status' => 'nullable|in:available,occupied,reserved',
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
            'status' => 'nullable|in:available,occupied,reserved',
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

        return response()->json($table);
    }

    /**
     * Generate QR code data for a table.
     */
    public function generateQr(Table $table)
    {
        return response()->json([
            'table' => $table,
            'qr_url' => $table->qr_url,
            'qr_data' => $table->qr_url, // Data to encode in QR
        ]);
    }
}
