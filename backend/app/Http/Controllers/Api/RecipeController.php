<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index(Product $product)
    {
        return response()->json($product->recipes()->with('inventoryItem')->get());
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        // Cek apakah sudah ada
        $existing = $product->recipes()->where('inventory_item_id', $validated['inventory_item_id'])->first();
        if ($existing) {
            $existing->update(['quantity' => $validated['quantity']]);
            return response()->json($existing->load('inventoryItem'));
        }

        $recipe = $product->recipes()->create($validated);

        return response()->json($recipe->load('inventoryItem'), 201);
    }

    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $recipe->update($validated);

        return response()->json($recipe->load('inventoryItem'));
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();

        return response()->json(null, 204);
    }

    public function bulkStore(Request $request, Product $product)
    {
        $validated = $request->validate([
            'recipes' => 'required|array|min:1',
            'recipes.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'recipes.*.quantity' => 'required|numeric|min:0.01',
        ]);

        // Hapus resep lama
        $product->recipes()->delete();

        // Buat resep baru
        foreach ($validated['recipes'] as $recipeData) {
            $product->recipes()->create($recipeData);
        }

        return response()->json($product->recipes()->with('inventoryItem')->get());
    }
}
