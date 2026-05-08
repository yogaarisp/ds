<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Xendit\Xendit;
use Xendit\Invoice;

class XenditService
{
    public function __construct()
    {
        Xendit::setApiKey(Setting::get('xendit_secret_key'));
    }

    public function createInvoice(Transaction $transaction)
    {
        $params = [
            'external_id' => $transaction->invoice_number . '-' . time(),
            'amount' => (int) $transaction->total,
            'description' => 'Pembayaran Order ' . $transaction->invoice_number,
            'customer' => [
                'given_names' => $transaction->customer_name ?? ($transaction->customer->name ?? 'Guest'),
                'mobile_number' => $transaction->customer_phone ?? ($transaction->customer->phone ?? ''),
            ],
            'items' => $transaction->items->map(function ($item) {
                return [
                    'name' => $item->product->name ?? ($item->package_combination->name ?? 'Item'),
                    'quantity' => (int) $item->quantity,
                    'price' => (int) $item->unit_price,
                ];
            })->toArray(),
            'success_redirect_url' => config('app.frontend_url') . '/transactions',
            'failure_redirect_url' => config('app.frontend_url') . '/pos',
        ];

        return Invoice::create($params);
    }
}
