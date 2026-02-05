<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Package;
use App\Models\PackageCombination;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private PricingService $pricingService,
        private StockService $stockService
    ) {}

    /**
     * Buat transaksi baru
     */
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Get customer jika ada
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::find($data['customer_id']);
            }

            // Use frontend calculated values if available, otherwise calculate
            if (isset($data['subtotal']) && isset($data['total'])) {
                $subtotal = $data['subtotal'];
                $total = $data['total'];
                $discount = $data['discount'] ?? 0;
            } else {
                // Fallback to backend calculation
                $subtotal = $this->calculateSubtotal($data['items'], $customer);
                $discount = $data['discount'] ?? 0;
                $total = $this->pricingService->calculateTotal($subtotal, $discount);
            }

            // Buat transaksi
            $transaction = Transaction::create([
                'invoice_number' => Transaction::generateInvoiceNumber(),
                'user_id' => $data['user_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'table_id' => $data['table_id'] ?? null,
                'order_type' => $data['order_type'],
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $data['paid_amount'] ?? 0,
                'change_amount' => $this->pricingService->calculateChange($total, $data['paid_amount'] ?? 0),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            // Buat transaction items
            $this->createTransactionItems($transaction, $data['items'], $customer);

            return $transaction;
        });
    }

    /**
     * Update transaksi
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Get customer jika ada
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::find($data['customer_id']);
            }

            // Use frontend calculated values if available, otherwise calculate
            if (isset($data['subtotal']) && isset($data['total'])) {
                $subtotal = $data['subtotal'];
                $total = $data['total'];
                $discount = $data['discount'] ?? 0;
            } else {
                // Fallback to backend calculation
                $subtotal = $this->calculateSubtotal($data['items'], $customer);
                $discount = $data['discount'] ?? 0;
                $total = $this->pricingService->calculateTotal($subtotal, $discount);
            }

            // Update data transaksi
            $transaction->update([
                'customer_id' => $data['customer_id'] ?? null,
                'table_id' => $data['table_id'] ?? null,
                'order_type' => $data['order_type'],
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            // Hapus items lama
            $transaction->items()->delete();

            // Buat transaction items baru
            $this->createTransactionItems($transaction, $data['items'], $customer);

            return $transaction->fresh();
        });
    }

    /**
     * Hitung subtotal dari items
     */
    private function calculateSubtotal(array $items, ?Customer $customer = null): int
    {
        $subtotal = 0;

        foreach ($items as $item) {
            if ($item['type'] === 'product') {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = $this->pricingService->calculateProductPrice($product, $customer);
                $subtotal += $this->pricingService->calculateItemSubtotal($unitPrice, $item['quantity']);
            }

            if ($item['type'] === 'package') {
                $combination = PackageCombination::findOrFail($item['package_combination_id']);
                $isFrozen = $item['is_frozen'] ?? false;
                $unitPrice = $this->pricingService->calculateCombinationPrice($combination, $customer, $isFrozen);
                $subtotal += $this->pricingService->calculateItemSubtotal($unitPrice, $item['quantity']);
            }
        }

        return $subtotal;
    }

    /**
     * Buat transaction items
     */
    private function createTransactionItems(Transaction $transaction, array $items, ?Customer $customer = null): void
    {
        foreach ($items as $item) {
            $transactionItem = [
                'transaction_id' => $transaction->id,
                'item_type' => $item['type'],
                'quantity' => $item['quantity'],
            ];

            if ($item['type'] === 'product') {
                $product = Product::findOrFail($item['product_id']);
                
                // Use frontend unit price if provided, otherwise calculate
                if (isset($item['unit_price'])) {
                    $unitPrice = $item['unit_price'];
                } else {
                    $unitPrice = $this->pricingService->calculateProductPrice($product, $customer);
                }

                $transactionItem['product_id'] = $product->id;
                $transactionItem['unit_price'] = $unitPrice;
                $transactionItem['subtotal'] = $item['subtotal'] ?? ($unitPrice * $item['quantity']);
            }

            if ($item['type'] === 'package') {
                $combination = PackageCombination::with('package')->findOrFail($item['package_combination_id']);
                
                // Use frontend unit price if provided, otherwise calculate
                if (isset($item['unit_price'])) {
                    $unitPrice = $item['unit_price'];
                } else {
                    $isFrozen = $item['is_frozen'] ?? false;
                    $unitPrice = $this->pricingService->calculateCombinationPrice($combination, $customer, $isFrozen);
                }

                $transactionItem['package_id'] = $combination->package_id;
                $transactionItem['package_combination_id'] = $combination->id;
                $transactionItem['unit_price'] = $unitPrice;
                $transactionItem['subtotal'] = $item['subtotal'] ?? ($unitPrice * $item['quantity']);
                
                // Store frozen info in notes if needed
                if ($item['is_frozen'] ?? false) {
                    $transactionItem['notes'] = 'Frozen';
                }
            }

            TransactionItem::create($transactionItem);
        }
    }

    /**
     * Complete transaksi (proses pembayaran & stok)
     */
    public function completeTransaction(Transaction $transaction, int $paidAmount, ?int $paymentMethodId = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $paidAmount, $paymentMethodId) {
            $transaction->update([
                'paid_amount' => $paidAmount,
                'change_amount' => $this->pricingService->calculateChange($transaction->total, $paidAmount),
                'status' => 'completed',
                'payment_method_id' => $paymentMethodId,
            ]);

            // Proses stok hanya untuk order instant
            if ($transaction->isInstant()) {
                $transaction->load('items.product', 'items.packageCombination.items.product');
                $this->stockService->processTransactionStock($transaction);
            }

            // Update customer stats jika ada customer
            if ($transaction->customer_id) {
                // Add point (1 transaction = 1 point)
                $transaction->customer->increment('points');

                $transaction->customer->updateStats();
            }

            return $transaction->fresh();
        });
    }

    /**
     * Process DP (Down Payment) - partial payment
     */
    public function processDpPayment(Transaction $transaction, int $dpAmount, ?int $paymentMethodId = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $dpAmount, $paymentMethodId) {
            $remainingAmount = $transaction->total - $dpAmount;

            $transaction->update([
                'dp_amount' => $dpAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => 'dp',
                'dp_paid_at' => now(),
                'status' => 'completed', // Mark as completed but with DP status
                'payment_method_id' => $paymentMethodId,
            ]);

            // Proses stok hanya untuk order instant
            if ($transaction->isInstant()) {
                $transaction->load('items.product', 'items.packageCombination.items.product');
                $this->stockService->processTransactionStock($transaction);
            }

            // Update customer stats jika ada customer
            if ($transaction->customer_id) {
                $transaction->customer->increment('points');
                $transaction->customer->updateStats();
            }

            return $transaction->fresh();
        });
    }

    /**
     * Pay remaining balance after DP
     */
    public function payDpRemaining(Transaction $transaction, int $paidAmount, ?int $paymentMethodId = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $paidAmount, $paymentMethodId) {
            $remainingAmount = $transaction->remaining_amount;
            $changeAmount = max(0, $paidAmount - $remainingAmount);

            $transaction->update([
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'remaining_amount' => 0,
                'payment_status' => 'paid',
                'fully_paid_at' => now(),
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Cancel transaksi
     */
    public function cancelTransaction(Transaction $transaction): Transaction
    {
        $transaction->update(['status' => 'cancelled']);
        return $transaction->fresh();
    }

    /**
     * Proses pre-order yang sudah waktunya
     */
    public function processScheduledOrders(): void
    {
        $orders = Transaction::scheduled()
            ->where('status', 'completed')
            ->where('scheduled_date', today())
            ->get();

        foreach ($orders as $order) {
            $order->load('items.product', 'items.packageCombination.items.product');
            $this->stockService->processTransactionStock($order);
        }
    }
}
