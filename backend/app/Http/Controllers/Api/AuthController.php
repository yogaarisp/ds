<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $identity = $request->email;
        $password = $request->password;

        // 1. Try Standard User Login (Admin/Staff)
        $user = User::where('email', $identity)->first();
        if ($user && Hash::check($password, $user->password)) {
            if (!$user->is_active) {
                throw ValidationException::withMessages(['email' => ['Akun tidak aktif.']]);
            }
            $token = $user->createToken('pos-token')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
                'is_customer' => false
            ]);
        }

        // 2. Try Customer Login (Email or Phone)
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $customer = Customer::where($field, $identity)->first();

        if ($customer && Hash::check($password, $customer->password)) {
            if (!$customer->is_active) {
                throw ValidationException::withMessages(['email' => ['Akun customer tidak aktif.']]);
            }

            $token = $customer->createToken('customer-token')->plainTextToken;
            
            $customer->loadCount(['coupons' => function ($query) {
                $query->where('customer_coupons.is_used', false)
                      ->where('coupons.is_active', true)
                      ->where(function ($q) {
                          $q->whereNull('coupons.end_date')
                            ->orWhere('coupons.end_date', '>=', now());
                      });
            }]);

            return response()->json([
                'user' => $customer,
                'customer' => $customer,
                'token' => $token,
                'is_customer' => true
            ]);
        }

        // 3. Both failed
        throw ValidationException::withMessages([
            'email' => ['Kredensial tidak valid. Silakan periksa kembali email/nomor HP dan password Anda.'],
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required_without:phone|email|unique:customers,email|nullable',
            'phone' => 'required_without:email|string|unique:customers,phone|nullable',
            'password' => 'required|string|min:6', // removed confirmed for simplicity unless frontend sends password_confirmation
        ]);

        $type = CustomerType::where('name', 'Reguler')->first() ?? CustomerType::first();

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'address' => $request->address ?? '-',
            'customer_type_id' => $type ? $type->id : 1,
            'is_active' => true,
        ]);

        $token = $customer->createToken('customer-token')->plainTextToken;

        return response()->json([
            'user' => $customer,
            'token' => $token,
            'is_customer' => true
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        if ($user instanceof Customer) {
            $user->loadCount(['coupons' => function ($query) {
                $query->where('customer_coupons.is_used', false)
                      ->where('coupons.is_active', true)
                      ->where(function ($q) {
                          $q->whereNull('coupons.end_date')
                            ->orWhere('coupons.end_date', '>=', now());
                      });
            }]);
        }

        return response()->json($user);
    }
}
