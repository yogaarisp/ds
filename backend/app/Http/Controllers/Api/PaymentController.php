<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\MidtransService;
use App\Services\XenditService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private MidtransService $midtransService,
        private XenditService $xenditService,
        private TransactionService $transactionService
    ) {}

    public function getMidtransSnapToken(Transaction $transaction)
    {
        try {
            $token = $this->midtransService->createSnapToken($transaction);
            return response()->json(['token' => $token]);
        } catch (\Exception $e) {
            Log::error('Midtrans Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat token pembayaran'], 500);
        }
    }

    public function getXenditInvoice(Transaction $transaction)
    {
        try {
            $invoice = $this->xenditService->createInvoice($transaction);
            return response()->json(['invoice_url' => $invoice['invoice_url']]);
        } catch (\Exception $e) {
            Log::error('Xendit Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat invoice Xendit'], 500);
        }
    }

    public function handleMidtransCallback(Request $request)
    {
        // Simple callback handling for now
        // In production, we should verify the signature key
        $orderId = explode('-', $request->order_id)[0];
        $status = $request->transaction_status;

        $transaction = Transaction::where('invoice_number', $orderId)->first();
        if (!$transaction) return response()->json(['message' => 'Not found'], 404);

        if (in_array($status, ['capture', 'settlement'])) {
            $this->transactionService->completeTransaction($transaction, $transaction->total);
        } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            $transaction->update(['status' => 'cancelled']);
        }

        return response()->json(['status' => 'OK']);
    }

    public function handleXenditCallback(Request $request)
    {
        // Simple callback handling for now
        // In production, we should verify using x-callback-token
        $orderId = explode('-', $request->external_id)[0];
        $status = $request->status;

        $transaction = Transaction::where('invoice_number', $orderId)->first();
        if (!$transaction) return response()->json(['message' => 'Not found'], 404);

        if ($status === 'PAID') {
            $this->transactionService->completeTransaction($transaction, $transaction->total);
        } elseif ($status === 'EXPIRED') {
            $transaction->update(['status' => 'cancelled']);
        }

        return response()->json(['status' => 'OK']);
    }
}
