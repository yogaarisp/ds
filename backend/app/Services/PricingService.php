<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PackageCombination;
use App\Models\Customer;

class PricingService
{
    /**
     * Hitung harga produk dengan customer level
     */
    public function calculateProductPrice(Product $product, ?Customer $customer = null): int
    {
        if (!$customer) {
            return $product->base_price;
        }
        
        // Cek apakah ada diskon khusus untuk produk ini berdasarkan level customer
        // Untuk sekarang, produk tidak punya diskon per level seperti paket
        // Jadi hanya gunakan custom discount percentage customer
        if ($customer->discount_percentage > 0) {
            $discount = (int) ($product->base_price * $customer->discount_percentage / 100);
            return max(0, $product->base_price - $discount);
        }
        
        return $product->base_price;
    }

    /**
     * Hitung harga kombinasi paket dengan customer level dan frozen discount
     */
    public function calculateCombinationPrice(PackageCombination $combination, ?Customer $customer = null, bool $isFrozen = false): int
    {
        $basePrice = $combination->base_price;
        
        if ($customer) {
            // Cek diskon spesifik paket untuk level customer ini
            if ($combination->package) {
                // Ensure discounts are loaded or load them
                $combination->package->loadMissing('discounts');
                
                // Fix: Use customer_type_id instead of legacy 'level' string
                $typeDiscount = $combination->package->discounts
                    ->where('customer_type_id', $customer->customer_type_id)
                    ->first();

                if ($typeDiscount) {
                    $basePrice = max(0, $basePrice - $typeDiscount->discount_amount);
                }
            }
            
            // Jika tidak ada diskon khusus paket, cek custom discount percentage customer
            if ($customer->discount_percentage > 0) {
                $discount = (int) ($basePrice * $customer->discount_percentage / 100);
                $basePrice = max(0, $basePrice - $discount);
            }
        }
        
        // Apply frozen discount
        if ($isFrozen) {
            $basePrice = max(0, $basePrice - 1000);
        }
        
        return $basePrice;
    }

    /**
     * Hitung subtotal item
     */
    public function calculateItemSubtotal(int $unitPrice, int $quantity): int
    {
        return $unitPrice * $quantity;
    }

    /**
     * Hitung total transaksi
     */
    public function calculateTotal(int $subtotal, int $discount = 0): int
    {
        return max(0, $subtotal - $discount);
    }

    /**
     * Hitung kembalian
     */
    public function calculateChange(int $total, int $paidAmount): int
    {
        return max(0, $paidAmount - $total);
    }

    /**
     * Get semua harga produk untuk customer tertentu
     */
    public function getProductPricesForCustomer(?Customer $customer = null): array
    {
        $products = Product::active()->get();
        $prices = [];

        foreach ($products as $product) {
            $finalPrice = $this->calculateProductPrice($product, $customer);
            $discount = $product->base_price - $finalPrice;
            
            $prices[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'base_price' => $product->base_price,
                'final_price' => $finalPrice,
                'discount' => $discount,
                'customer_level' => $customer?->level ?? 'normal',
            ];
        }

        return $prices;
    }

    /**
     * Get semua harga kombinasi untuk customer tertentu
     */
    public function getCombinationPricesForCustomer(?Customer $customer = null): array
    {
        $combinations = PackageCombination::active()
            ->with('package.discounts')
            ->get();
        $prices = [];

        foreach ($combinations as $combination) {
            $finalPrice = $this->calculateCombinationPrice($combination, $customer, false); // Default not frozen
            $discount = $combination->base_price - $finalPrice;
            
            $prices[$combination->id] = [
                'combination_id' => $combination->id,
                'name' => $combination->name,
                'package_name' => $combination->package->name,
                'base_price' => $combination->base_price,
                'final_price' => $finalPrice,
                'discount' => $discount,
                'customer_level' => $customer?->level ?? 'normal',
            ];
        }

        return $prices;
    }
}
