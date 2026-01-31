<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Customer;
use App\Services\PricingService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        private PricingService $pricingService,
        private ImageService $imageService
    ) {}

    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('low_stock')) {
            $query->lowStock();
        }

        $products = $query->orderBy('name')->get();

        // Jika ada customer_id, tambahkan harga dengan level customer
        if ($request->has('customer_id')) {
            $customer = Customer::find($request->customer_id);
            $products = $products->map(function ($product) use ($customer) {
                $product->final_price = $this->pricingService->calculateProductPrice($product, $customer);
                $product->discount = $customer ? $customer->getDiscountAmount($product->base_price) : 0;
                $product->customer_level = $customer?->level ?? 'normal';
                return $product;
            });
        }

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'base_price' => 'required|integer|min:0',
            'stock' => 'integer|min:0',
            'min_stock' => 'integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|max:10240',
        ]);

        $data = $validated;
        unset($data['image']);
        
        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->store(
                $request->file('image'),
                'products',
                $validated['name']
            );
        }

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('recipes.inventoryItem'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'sku' => 'string|unique:products,sku,' . $product->id,
            'base_price' => 'integer|min:0',
            'stock' => 'integer|min:0',
            'min_stock' => 'integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|max:10240',
            'is_active' => 'boolean',
        ]);

        $data = $validated;
        unset($data['image']);
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            $this->imageService->delete($product->image);
            
            $name = $validated['name'] ?? $product->name;
            $data['image'] = $this->imageService->store(
                $request->file('image'),
                'products',
                $name
            );
        }

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        // Delete image if exists
        $this->imageService->delete($product->image);
        
        $product->delete();

        return response()->json(null, 204);
    }

    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $stockBefore = $product->stock;
        $stockAfter = $validated['stock'];
        $difference = $stockAfter - $stockBefore;

        // Determine type based on stock change
        if ($difference > 0) {
            $type = 'in';
            $quantity = $difference;
        } elseif ($difference < 0) {
            $type = 'out';
            $quantity = abs($difference);
        } else {
            // No change
            return response()->json($product);
        }

        $product->update(['stock' => $stockAfter]);

        // Record the stock transaction
        \App\Models\ProductStockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $validated['notes'] ?? 'Penyesuaian stok manual',
        ]);

        return response()->json($product);
    }
}
