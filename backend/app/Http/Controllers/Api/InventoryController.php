<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\StockService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $query = InventoryItem::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:inventory_items',
            'type' => 'required|in:raw_material,packaging',
            'unit' => 'required|string|max:50',
            'stock' => 'numeric|min:0',
            'min_stock' => 'numeric|min:0',
            'price_per_unit' => 'integer|min:0',
        ]);

        $item = InventoryItem::create($validated);

        return response()->json($item, 201);
    }

    public function show(InventoryItem $inventory)
    {
        return response()->json($inventory->load(['recipes.product', 'packagingRules']));
    }

    public function update(Request $request, InventoryItem $inventory)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|unique:inventory_items,code,' . $inventory->id,
            'type' => 'in:raw_material,packaging',
            'unit' => 'string|max:50',
            'min_stock' => 'numeric|min:0',
            'price_per_unit' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $inventory->update($validated);

        return response()->json($inventory);
    }

    public function destroy(InventoryItem $inventory)
    {
        $inventory->delete();

        return response()->json(null, 204);
    }

    public function addStock(Request $request, InventoryItem $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $transaction = $this->stockService->increaseInventoryStock(
            $inventory,
            $validated['quantity'],
            $request->user()->id,
            $validated['notes'] ?? null
        );

        return response()->json([
            'inventory' => $inventory->fresh(),
            'transaction' => $transaction,
        ]);
    }

    public function transactions(Request $request, InventoryItem $inventory)
    {
        $query = $inventory->inventoryTransactions()->with('user');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }

    public function lowStockWarning()
    {
        return response()->json([
            'products' => $this->stockService->getLowStockProducts(),
            'inventory' => $this->stockService->getLowStockInventory(),
        ]);
    }
}
