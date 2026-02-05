<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageCombination;
use App\Models\Customer;
use App\Services\PricingService;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(private PricingService $pricingService) {}

    public function index(Request $request)
    {
        $query = Package::with(['combinations.items.product', 'discounts']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $packages = $query->orderBy('name')->get();

        $customer = $request->has('customer_id') ? Customer::find($request->customer_id) : null;

        $packages = $packages->map(function ($package) use ($customer) {
            $maxPackageStock = 0;
            
            $package->combinations = $package->combinations->map(function ($combination) use ($customer, $package, &$maxPackageStock) {
                // Logic harga (hanya jika ada customer)
                if ($customer) {
                    $combination->final_price = $this->pricingService->calculateCombinationPrice($combination, $customer);
                    $combination->discount = $customer->getDiscountAmount($combination->base_price);
                    $combination->customer_level = $customer->customerType?->name;
                } else {
                    // Default values if needed
                    // $combination->final_price = $combination->base_price; // Or leave as is if frontend handles it
                }

                // Logic Stock (Always calculate)
                $minStock = null;
                foreach ($combination->items as $item) {
                    if ($item->product && $item->product->is_active) {
                         // production = product_stock / item_qty
                        $qtyNeeded = $item->quantity;
                        if ($qtyNeeded > 0) {
                            $itemStock = floor($item->product->stock / $qtyNeeded);
                            if ($minStock === null || $itemStock < $minStock) {
                                $minStock = $itemStock;
                            }
                        }
                    } else {
                         // \Illuminate\Support\Facades\Log::info("Item missing or inactive: " . json_encode($item));
                    }
                }
                $combination->stock = $minStock ?? 0;

                if ($combination->stock > $maxPackageStock) {
                    $maxPackageStock = $combination->stock;
                }

                return $combination;
            });

            $package->stock = $maxPackageStock;
            return $package;
        });

        return response()->json($packages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:packages',
            'type' => 'required|in:bundle,mix',
            'total_quantity' => 'required|integer|min:1',
            'variant_count' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $validated;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('packages', 'public');
            $data['image'] = $path;
        }

        $package = Package::create($data);

        return response()->json($package, 201);
    }

    public function show(Package $package)
    {
        return response()->json($package->load('combinations.items.product'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|unique:packages,code,' . $package->id,
            'type' => 'in:bundle,mix',
            'total_quantity' => 'integer|min:1',
            'variant_count' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $data = $validated;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('packages', 'public');
            $data['image'] = $path;
        }

        $package->update($data);

        return response()->json($package);
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return response()->json(null, 204);
    }

    // Kombinasi endpoints
    public function storeCombination(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_price' => 'required|integer|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'image' => 'nullable|image|max:2048',
        ]);

        $combination = $package->combinations()->create([
            'name' => $validated['name'],
            'base_price' => $validated['base_price'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('combinations', 'public');
            $combination->update(['image' => $path]);
        }

        foreach ($validated['items'] as $item) {
            $combination->items()->create($item);
        }

        return response()->json($combination->load('items.product'), 201);
    }

    public function updateCombination(Request $request, PackageCombination $combination)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'base_price' => 'integer|min:0',
            'is_active' => 'boolean',
            'items' => 'array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'image' => 'nullable|image|max:2048',
        ]);

        $combination->update($validated);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('combinations', 'public');
            $combination->update(['image' => $path]);
        }

        if (isset($validated['items'])) {
            $combination->items()->delete();
            foreach ($validated['items'] as $item) {
                $combination->items()->create($item);
            }
        }

        return response()->json($combination->load('items.product'));
    }

    public function destroyCombination(PackageCombination $combination)
    {
        $combination->delete();

        return response()->json(null, 204);
    }

    public function updateDiscounts(Request $request, Package $package)
    {
        $validated = $request->validate([
            'discounts' => 'array',
            'discounts.*.customer_type_id' => 'required|exists:customer_types,id',
            'discounts.*.amount' => 'required|integer|min:0',
        ]);

        // Delete existing discounts
        $package->discounts()->delete();

        if (!empty($validated['discounts'])) {
            foreach ($validated['discounts'] as $discount) {
                $package->discounts()->create([
                    'customer_type_id' => $discount['customer_type_id'],
                    'discount_amount' => $discount['amount'],
                ]);
            }
        }

        return response()->json($package->load('discounts'));
    }

    public function popular(Request $request)
    {
        $limit = $request->get('limit', 5);
        
        $combinations = PackageCombination::active()
            ->whereHas('package', function($q) {
                $q->active();
            })
            ->with(['package', 'items.product'])
            ->select('package_combinations.*')
            ->leftJoin('transaction_items', 'package_combinations.id', '=', 'transaction_items.package_combination_id')
            ->selectRaw('package_combinations.*, SUM(COALESCE(transaction_items.quantity, 0)) as total_sold')
            ->groupBy('package_combinations.id')
            ->orderByDesc('total_sold')
            ->orderByDesc('package_combinations.created_at')
            ->limit($limit)
            ->get()
            ->map(function($comb) {
                $pkg = $comb->package;
                
                // Formulate display details
                $comb->display_name = $comb->name;
                $comb->parent_name = $pkg->name;
                
                // Generate contents summary
                $comb->contents = $comb->items->map(function($item) {
                    return $item->quantity . 'x ' . ($item->product->name ?? 'Produk');
                })->implode(', ');
                
                // Fallback image to package image if combination image is missing
                $comb->display_image = $comb->image ?? $pkg->image;
                
                return $comb;
            });

        return response()->json($combinations);
    }
}
