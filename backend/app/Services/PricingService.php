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
     * Hitung harga kombinasi paket dengan customer level dan frozen discount.
     *
     * Urutan prioritas diskon:
     * 1. Cek combination_discounts (per-kombinasi, override) → jika ada, pakai ini
     * 2. Fallback ke package_discounts (level paket, berlaku semua kombinasi)
     * 3. Jika masih belum ada, cek discount_percentage pada customer (persentase individual)
     * 4. Jika isFrozen → kurangi 1000 lagi
     */
    public function calculateCombinationPrice(PackageCombination $combination, ?Customer $customer = null, bool $isFrozen = false): int
    {
        $basePrice = $combination->base_price;

        if ($customer) {
            $discountApplied = false;

            // ── Prioritas 1: Combination-level discount override ──────────────
            $combination->loadMissing('discounts');
            $combinationDiscount = $combination->discounts
                ->where('customer_type_id', $customer->customer_type_id)
                ->first();

            if ($combinationDiscount) {
                $basePrice = max(0, $basePrice - $combinationDiscount->discount_amount);
                $discountApplied = true;
            }

            // ── Prioritas 2: Package-level discount (fallback) ─────────────────
            if (!$discountApplied && $combination->package) {
                $combination->package->loadMissing('discounts');

                $packageDiscount = $combination->package->discounts
                    ->where('customer_type_id', $customer->customer_type_id)
                    ->first();

                if ($packageDiscount) {
                    $basePrice = max(0, $basePrice - $packageDiscount->discount_amount);
                    $discountApplied = true;
                }
            }

            // ── Prioritas 3: Persentase diskon individual customer ─────────────
            if ($customer->discount_percentage > 0) {
                $discount = (int) ($basePrice * $customer->discount_percentage / 100);
                $basePrice = max(0, $basePrice - $discount);
            }
        }

        // ── Apply frozen discount ──────────────────────────────────────────────
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
