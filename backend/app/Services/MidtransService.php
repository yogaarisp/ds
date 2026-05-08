<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = Setting::get('midtrans_server_key');
        Config::$isProduction = Setting::get('midtrans_mode') === 'production';
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createSnapToken(Transaction $transaction)
    {
        $params = [
            'transaction_details' => [
                'order_id' => $transaction->invoice_number . '-' . time(),
                'gross_amount' => (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name' => $transaction->customer_name ?? ($transaction->customer->name ?? 'Guest'),
                'phone' => $transaction->customer_phone ?? ($transaction->customer->phone ?? ''),
            ],
            'item_details' => $transaction->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'price' => (int) $item->unit_price,
                    'quantity' => (int) $item->quantity,
                    'name' => $item->product->name ?? ($item->package_combination->name ?? 'Item'),
                ];
            })->toArray(),
        ];

        return Snap::getSnapToken($params);
    }
}
