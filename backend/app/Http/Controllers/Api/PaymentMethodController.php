<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMethod::query();

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,transfer,qris,other',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $paymentMethod = PaymentMethod::create($validated);

        return response()->json($paymentMethod, 201);
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'type' => 'in:cash,transfer,qris,other',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $paymentMethod->update($validated);

        return response()->json($paymentMethod);
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return response()->json(['message' => 'Payment method deleted']);
    }
}
