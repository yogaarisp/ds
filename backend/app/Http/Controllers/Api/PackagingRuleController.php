<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PackagingRule;
use Illuminate\Http\Request;

class PackagingRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = PackagingRule::with('inventoryItem');

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'rule_type' => 'required|in:per_quantity,per_order',
            'trigger_quantity' => 'required_if:rule_type,per_quantity|nullable|integer|min:1',
            'usage_quantity' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        $rule = PackagingRule::create($validated);

        return response()->json($rule->load('inventoryItem'), 201);
    }

    public function show(PackagingRule $packagingRule)
    {
        return response()->json($packagingRule->load('inventoryItem'));
    }

    public function update(Request $request, PackagingRule $packagingRule)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'exists:inventory_items,id',
            'rule_type' => 'in:per_quantity,per_order',
            'trigger_quantity' => 'nullable|integer|min:1',
            'usage_quantity' => 'numeric|min:0.01',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $packagingRule->update($validated);

        return response()->json($packagingRule->load('inventoryItem'));
    }

    public function destroy(PackagingRule $packagingRule)
    {
        $packagingRule->delete();

        return response()->json(null, 204);
    }
}
