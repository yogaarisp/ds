<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'customer', 'table', 'items.product', 'items.package', 'items.packageCombination', 'paymentMethod']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereDate('created_at', '>=', $request->from_date)
                ->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->boolean('today')) {
            $query->today();
        }

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->get('per_page', 10);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'order_type' => 'required|in:instant,scheduled',
            'scheduled_date' => 'required_if:order_type,scheduled|date|after_or_equal:today',
            'scheduled_time' => 'nullable|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'discount' => 'integer|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:product,package',
            'items.*.product_id' => 'required_if:items.*.type,product|exists:products,id',
            'items.*.package_combination_id' => 'required_if:items.*.type,package|exists:package_combinations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.is_frozen' => 'sometimes|boolean',
            'items.*.unit_price' => 'sometimes|integer|min:0',
            'table_code' => 'nullable|string|exists:tables,code',
        ]);

        $validated['user_id'] = auth('sanctum')->id();

        // Handle table code
        if (!empty($validated['table_code'])) {
            $table = \App\Models\Table::where('code', $validated['table_code'])->first();
            if ($table) {
                $validated['table_id'] = $table->id;
            }
            unset($validated['table_code']);
        }

        $transaction = $this->transactionService->createTransaction($validated);

        return response()->json($transaction->load(['items.product', 'items.package', 'items.packageCombination', 'customer', 'user']), 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Hanya transaksi pending yang bisa diedit'], 422);
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'order_type' => 'required|in:instant,scheduled',
            'scheduled_date' => 'required_if:order_type,scheduled|date', // Allow past dates for existing transactions
            'scheduled_time' => 'nullable|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'discount' => 'integer|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:product,package',
            'items.*.product_id' => 'required_if:items.*.type,product|exists:products,id',
            'items.*.package_combination_id' => 'required_if:items.*.type,package|exists:package_combinations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.is_frozen' => 'sometimes|boolean',
            'items.*.unit_price' => 'sometimes|integer|min:0',
        ]);

        $transaction = $this->transactionService->updateTransaction($transaction, $validated);

        return response()->json($transaction->load(['items.product', 'items.package', 'items.packageCombination', 'customer']));
    }

    public function show(Transaction $transaction)
    {
        return response()->json($transaction->load([
            'user',
            'customer',
            'table',
            'items.product',
            'items.package',
            'items.packageCombination.items.product',
            'paymentMethod'
        ]));
    }

    public function complete(Request $request, Transaction $transaction)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_type' => 'nullable|in:full,dp', // full = bayar lunas, dp = bayar DP
            'dp_amount' => 'nullable|integer|min:0',
        ]);

        $paymentMethod = \App\Models\PaymentMethod::findOrFail($request->payment_method_id);
        $paidAmount = $request->input('paid_amount');
        $paymentType = $request->input('payment_type', 'full');
        $dpAmount = $request->input('dp_amount', 0);

        // Handle DP payment
        if ($paymentType === 'dp') {
            if ($dpAmount <= 0) {
                return response()->json([
                    'message' => 'Jumlah DP harus lebih dari 0',
                    'errors' => ['dp_amount' => ['Jumlah DP tidak valid']]
                ], 422);
            }

            if ($dpAmount >= $transaction->total) {
                return response()->json([
                    'message' => 'Jumlah DP harus kurang dari total. Gunakan pembayaran lunas jika ingin bayar penuh.',
                    'errors' => ['dp_amount' => ['DP harus kurang dari total']]
                ], 422);
            }

            // Process DP payment
            $transaction = $this->transactionService->processDpPayment(
                $transaction,
                $dpAmount,
                $paymentMethod->id
            );

            return response()->json($transaction->load(['items.product', 'items.package', 'items.packageCombination', 'customer', 'user', 'paymentMethod']));
        }

        // Full payment logic (existing)
        if ($paymentMethod->type === 'cash') {
            if ($paidAmount < $transaction->total) {
                return response()->json([
                    'message' => 'The paid amount field must be at least ' . $transaction->total,
                    'errors' => ['paid_amount' => ['Nominal pembayaran kurang']]
                ], 422);
            }
        } else {
            // Non-cash always considered paid in full matches total
            $paidAmount = $transaction->total;
        }

        $transaction = $this->transactionService->completeTransaction(
            $transaction,
            $paidAmount,
            $paymentMethod->id
        );

        return response()->json($transaction->load(['items.product', 'items.package', 'items.packageCombination', 'customer', 'user', 'paymentMethod']));
    }

    /**
     * Pay the remaining balance after DP
     */
    public function payRemaining(Request $request, Transaction $transaction)
    {
        if ($transaction->payment_status !== 'dp') {
            return response()->json(['message' => 'Transaksi ini tidak memiliki sisa pembayaran DP'], 422);
        }

        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        $paymentMethod = \App\Models\PaymentMethod::findOrFail($request->payment_method_id);
        $paidAmount = $request->input('paid_amount');
        $remainingAmount = $transaction->remaining_amount;

        // For cash, validate amount
        if ($paymentMethod->type === 'cash') {
            if ($paidAmount < $remainingAmount) {
                return response()->json([
                    'message' => 'Nominal pembayaran kurang dari sisa tagihan',
                    'errors' => ['paid_amount' => ['Nominal pembayaran kurang']]
                ], 422);
            }
        } else {
            $paidAmount = $remainingAmount;
        }

        $transaction = $this->transactionService->payDpRemaining(
            $transaction,
            $paidAmount,
            $paymentMethod->id
        );

        return response()->json($transaction->load(['items.product', 'items.package', 'items.packageCombination', 'customer', 'user', 'paymentMethod']));
    }

    public function cancel(Transaction $transaction)
    {
        if ($transaction->isCompleted()) {
            return response()->json(['message' => 'Transaksi sudah selesai, tidak bisa dibatalkan'], 422);
        }

        $transaction = $this->transactionService->cancelTransaction($transaction);

        return response()->json($transaction);
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }

    public function todaySummary()
    {
        $transactions = Transaction::today()->completed()->get();

        return response()->json([
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total'),
            'total_items' => $transactions->sum(fn($t) => $t->items->sum('quantity')),
        ]);
    }

    public function upcomingOrders()
    {
        $orders = Transaction::upcoming()
            ->with(['customer', 'items.product', 'items.packageCombination'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return response()->json($orders);
    }

    public function getPendingByCustomer(Request $request)
    {
        try {
            $customerId = $request->get('customer_id');

            if (!$customerId) {
                return response()->json(['error' => 'customer_id is required'], 400);
            }

            $pendingTransaction = Transaction::where('customer_id', $customerId)
                ->where('status', 'pending')
                ->with(['items.product', 'items.package', 'items.packageCombination'])
                ->first();

            return response()->json([
                'data' => $pendingTransaction
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getPendingByCustomer: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get guest orders by phone number
     */
    public function getGuestOrders(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20'
        ]);

        $phone = $request->get('phone');

        try {
            $orders = Transaction::where('customer_phone', $phone)
                ->whereNull('customer_id') // Guest orders only
                ->with(['items.product', 'items.package', 'items.packageCombination', 'table', 'paymentMethod'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'data' => $orders,
                'count' => $orders->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getGuestOrders: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
