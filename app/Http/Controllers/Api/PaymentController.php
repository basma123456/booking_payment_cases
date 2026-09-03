<?php

namespace App\Http\Controllers\Api;


use App\Http\Requests\CreatePaymentRequest;
use App\Models\Purchase;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PaymentController extends Controller
{
    public function store(
        CreatePaymentRequest $request,
        Purchase $purchase,
        PaymentService $payments
    ): JsonResponse {
        abort_unless($purchase->user_id === $request->user()->id, 404);

        $result = $payments->createPayment(
            $purchase,
            $request->input('payment_method', config('myfatoorah.default_payment_method', 'CARD'))
        );

        return response()->json(['data' => $result], 201);
    }

    public function callback(): JsonResponse
    {
        return response()->json([
            'message' => 'Payment callback received. Payment status is confirmed server-side.',
        ]);
    }
}
