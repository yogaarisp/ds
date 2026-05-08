<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    /**
     * Search for an order by invoice number for Telegram Bot interaction
     */
    public function searchOrder(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string',
        ]);

        $invoice = strtoupper($request->invoice_number);

        $transaction = Transaction::with(['customer', 'items.product', 'items.package', 'table', 'user'])
            ->where('invoice_number', $invoice)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => "Maaf, pesanan dengan nomor invoice {$invoice} tidak ditemukan."
            ], 404);
        }

        // Format items for easy use in n8n
        $items = [];
        foreach ($transaction->items as $item) {
            $name = '';
            if ($item->item_type === 'product' && $item->product) {
                $name = $item->product->name;
            } elseif ($item->item_type === 'package') {
                $packageName = $item->package ? $item->package->name : 'Paket';
                $combName = $item->packageCombination ? $item->packageCombination->name : '';
                $name = $combName ? "{$packageName} - {$combName}" : $packageName;
            }

            $items[] = [
                'name' => $name,
                'qty' => $item->quantity,
                'subtotal' => $item->subtotal,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->customer ? $transaction->customer->name : ($transaction->customer_name ?: 'Walk-in'),
                'order_type' => $transaction->order_type,
                'status' => $transaction->status,
                'total' => $transaction->total,
                'table' => $transaction->table ? $transaction->table->name : '-',
                'created_at' => $transaction->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                'items' => $items,
                'cashier' => $transaction->user ? $transaction->user->name : '-'
            ]
        ]);
    }
}
