<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerType;
use Illuminate\Http\Request;

class CustomerTypeController extends Controller
{
    public function index()
    {
        return CustomerType::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:customer_types|max:255',
            'description' => 'nullable|string',
        ]);

        $customerType = CustomerType::create($validated);

        return response()->json($customerType, 201);
    }

    public function show(CustomerType $customerType)
    {
        return $customerType;
    }

    public function update(Request $request, CustomerType $customerType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:customer_types,name,' . $customerType->id,
            'description' => 'nullable|string',
        ]);

        $customerType->update($validated);

        return response()->json($customerType);
    }

    public function destroy(CustomerType $customerType)
    {
        // Prevent deleting types that are in use
        // Ideally checking relationships but simpler here:
        try {
            $customerType->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
             return response()->json(['message' => 'Cannot delete type in use'], 422);
        }
    }
}
