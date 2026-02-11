<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

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
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $this->imageService->upload($request->file('avatar'), 'avatars');
        }

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
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($customer->avatar) {
                $this->imageService->delete($customer->avatar);
            }
            $validated['avatar'] = $this->imageService->upload($request->file('avatar'), 'avatars');
        }

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
        // Delete avatar if exists
        if ($customer->avatar) {
            $this->imageService->delete($customer->avatar);
        }
        
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

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $customer = auth()->user();
        
        // Verify current password
        if (!Hash::check($validated['current_password'], $customer->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak benar',
                'errors' => [
                    'current_password' => ['Password saat ini tidak benar']
                ]
            ], 422);
        }

        // Update password
        $customer->update([
            'password' => Hash::make($validated['new_password'])
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah'
        ]);
    }
}
