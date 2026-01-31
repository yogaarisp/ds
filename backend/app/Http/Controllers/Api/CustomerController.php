<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(20);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers',
            'email' => 'nullable|email|unique:customers',
            'address' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'type' => 'in:regular,vip',
            'customer_type_id' => 'required|exists:customer_types,id',
            'discount_percentage' => 'integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $password = $request->input('password') ?: 'dimsumsarang';
        $validated['password'] = Hash::make($password);

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer->load('transactions.items'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'phone' => 'string|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'address' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'type' => 'in:regular,vip',
            'customer_type_id' => 'required|exists:customer_types,id',
            'discount_percentage' => 'integer|min:0|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->input('password'));
        } else {
            unset($validated['password']);
        }

        $customer->update($validated);

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(null, 204);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = Customer::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(10)
            ->with('customerType')
            ->get(['id', 'name', 'phone', 'type', 'customer_type_id']);

        return response()->json($customers);
    }

    public function stats(Customer $customer)
    {
        $customer->updateStats();
        
        $transactions = $customer->transactions()
            ->completed()
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'customer' => $customer->fresh(),
            'recent_transactions' => $transactions,
            'monthly_spending' => $customer->transactions()
                ->completed()
                ->whereMonth('created_at', now()->month)
                ->sum('total'),
        ]);
    }
}
