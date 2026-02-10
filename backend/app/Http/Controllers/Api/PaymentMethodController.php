<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\ImageService;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

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
            'qr_code_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
        ]);

        // Handle QR code image upload
        if ($request->hasFile('qr_code_image')) {
            $validated['qr_code_image'] = $this->imageService->store($request->file('qr_code_image'), 'qr-codes');
        }

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
            'qr_code_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
        ]);

        // Handle QR code image upload
        if ($request->hasFile('qr_code_image')) {
            // Delete old image if exists
            if ($paymentMethod->qr_code_image) {
                $this->imageService->delete($paymentMethod->qr_code_image);
            }
            $validated['qr_code_image'] = $this->imageService->store($request->file('qr_code_image'), 'qr-codes');
        }

        $paymentMethod->update($validated);

        return response()->json($paymentMethod);
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        // Delete QR code image if exists
        if ($paymentMethod->qr_code_image) {
            $this->imageService->delete($paymentMethod->qr_code_image);
        }

        $paymentMethod->delete();

        return response()->json(['message' => 'Payment method deleted']);
    }
}

