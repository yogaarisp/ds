<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAddressController extends Controller
{
    /**
     * Display a listing of customer addresses.
     */
    public function index()
    {
        $addresses = CustomerAddress::where('customer_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($addresses);
    }

    /**
     * Store a newly created address.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'is_default' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $validated['customer_id'] = Auth::id();

        $address = CustomerAddress::create($validated);

        // If this is set as default, update other addresses
        if ($validated['is_default'] ?? false) {
            $address->setAsDefault();
        }

        return response()->json($address, 201);
    }

    /**
     * Display the specified address.
     */
    public function show(CustomerAddress $address)
    {
        // Ensure the address belongs to the authenticated customer
        if ($address->customer_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($address);
    }

    /**
     * Update the specified address.
     */
    public function update(Request $request, CustomerAddress $address)
    {
        // Ensure the address belongs to the authenticated customer
        if ($address->customer_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'label' => 'sometimes|required|string|max:255',
            'recipient_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'is_default' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $address->update($validated);

        // If this is set as default, update other addresses
        if ($validated['is_default'] ?? false) {
            $address->setAsDefault();
        }

        return response()->json($address);
    }

    /**
     * Remove the specified address.
     */
    public function destroy(CustomerAddress $address)
    {
        // Ensure the address belongs to the authenticated customer
        if ($address->customer_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If this is the default address, set another address as default
        if ($address->is_default) {
            $nextAddress = CustomerAddress::where('customer_id', Auth::id())
                ->where('id', '!=', $address->id)
                ->first();

            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        $address->delete();

        return response()->json(null, 204);
    }

    /**
     * Set an address as default.
     */
    public function setDefault(CustomerAddress $address)
    {
        // Ensure the address belongs to the authenticated customer
        if ($address->customer_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $address->setAsDefault();

        return response()->json(['message' => 'Address set as default']);
    }
}