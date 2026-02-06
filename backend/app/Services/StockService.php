<?php

namespace App\Services;

use App\Models\Product;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PackagingRule;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Kurangi stok produk dengan tracking
     */
    public function decreaseProductStock(Product $product, int $quantity): void
    {
        $stockBefore = $product->stock;
        $product->decrement('stock', $quantity);
        
        // Create product stock transaction record
        $this->createProductStockTransaction($product, $quantity, $stockBefore, 'out');
    }

    /**
     * Tambah stok produk dengan tracking
     */
    public function increaseProductStock(Product $product, int $quantity): void
    {
        $stockBefore = $product->stock;
        $product->increment('stock', $quantity);
        
        // Create product stock transaction record
        $this->createProductStockTransaction($product, $quantity, $stockBefore, 'in');
    }

    /**
     * Create product stock transaction record
     */
    private function createProductStockTransaction(Product $product, int $quantity, int $stockBefore, string $type): void
    {
        \App\Models\ProductStockTransaction::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $product->fresh()->stock,
            'notes' => $type === 'out' ? 'Penjualan' : 'Penambahan stok',
            'user_id' => auth()->id() ?? 1, // Default to user 1 if no auth
        ]);
    }

    /**
     * Kurangi stok inventory (bahan baku / packaging)
     */
    public function decreaseInventoryStock(
        InventoryItem $item,
        float $quantity,
        ?int $userId,
        ?int $transactionId = null,
        ?string $notes = null
    ): InventoryTransaction {
        $stockBefore = $item->stock;
        $item->decrement('stock', $quantity);

        return InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'user_id' => $userId, // Can be null now
            'transaction_id' => $transactionId,
            'type' => 'out',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $item->fresh()->stock,
            'notes' => $notes,
        ]);
    }

    /**
     * Tambah stok inventory (bahan baku / packaging)
     */
    public function increaseInventoryStock(
        InventoryItem $item,
        float $quantity,
        ?int $userId,
        ?string $notes = null
    ): InventoryTransaction {
        $stockBefore = $item->stock;
        $item->increment('stock', $quantity);

        return InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'user_id' => $userId, // Can be null now
            'type' => 'in',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $item->fresh()->stock,
            'notes' => $notes,
        ]);
    }

    /**
     * Proses pengurangan stok dari transaksi (SISTEM BARU - PRODUK ONLY)
     */
    public function processTransactionStock(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            foreach ($transaction->items as $item) {
                $this->processTransactionItem($item, $transaction);
            }
        });
    }

    /**
     * Proses item transaksi (SISTEM BARU - PRODUK ONLY)
     */
    private function processTransactionItem(TransactionItem $item, Transaction $transaction): int
    {
        $totalPcs = 0;

        if ($item->isProduct() && $item->product) {
            // Kurangi stok produk langsung
            $this->decreaseProductStockFromTransaction($item->product, $item->quantity, $transaction);
            $totalPcs = $item->quantity;
            
            // Log untuk tracking
            \Log::info("Stock reduced: {$item->product->name} x {$item->quantity} (Transaction: {$transaction->invoice_number})");
        }

        if ($item->isPackage() && $item->packageCombination) {
            // Proses setiap produk dalam kombinasi paket
            foreach ($item->packageCombination->items as $combItem) {
                $qty = $combItem->quantity * $item->quantity;
                $this->decreaseProductStockFromTransaction($combItem->product, $qty, $transaction);
                
                // Log untuk tracking
                \Log::info("Stock reduced from package: {$combItem->product->name} x {$qty} (Package: {$item->packageCombination->name}, Transaction: {$transaction->invoice_number})");
            }

            $totalPcs = $item->package->total_quantity * $item->quantity;
        }

        return $totalPcs;
    }

    /**
     * Kurangi stok produk dari transaksi dengan tracking lengkap
     */
    public function decreaseProductStockFromTransaction(Product $product, int $quantity, Transaction $transaction): void
    {
        $stockBefore = $product->stock;
        $product->decrement('stock', $quantity);
        
        // Create product stock transaction record dengan transaction_id
        \App\Models\ProductStockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'type' => 'out',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $product->fresh()->stock,
            'notes' => "Penjualan - {$transaction->invoice_number}",
        ]);
    }

    /**
     * Proses pengurangan bahan baku berdasarkan resep
     */
    private function processRecipes(Product $product, int $quantity, Transaction $transaction): void
    {
        foreach ($product->recipes as $recipe) {
            $usageQty = $recipe->quantity * $quantity;
            $this->decreaseInventoryStock(
                $recipe->inventoryItem,
                $usageQty,
                $transaction->user_id,
                $transaction->id,
                "Pemakaian untuk {$product->name} x {$quantity}"
            );
        }
    }

    /**
     * Proses packaging rules
     */
    private function processPackagingRules(int $totalPcs, Transaction $transaction): void
    {
        $rules = PackagingRule::active()->with('inventoryItem')->get();

        foreach ($rules as $rule) {
            $usage = $rule->calculateUsage($totalPcs);

            if ($usage > 0) {
                $this->decreaseInventoryStock(
                    $rule->inventoryItem,
                    $usage,
                    $transaction->user_id,
                    $transaction->id,
                    "Packaging: {$rule->description}"
                );
            }
        }
    }

    /**
     * Get produk dengan stok rendah
     */
    public function getLowStockProducts()
    {
        return Product::active()->lowStock()->get();
    }

    /**
     * Get inventory dengan stok rendah
     */
    public function getLowStockInventory()
    {
        return InventoryItem::active()->lowStock()->get();
    }
}
