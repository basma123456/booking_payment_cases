<?php

namespace App\Http\Controllers\Api;


use App\Http\Requests\CreatePurchaseRequest;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PurchaseController extends Controller
{
    public function store(CreatePurchaseRequest $request, PurchaseService $service): JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        abort_unless($key, 422, 'Idempotency-Key header is required.');

        $purchase = $service->create(
            $request->user()->id,
            (int) $request->integer('service_id'),
            $key
        );

        return response()->json([
            'data' => $purchase,
        ], 201);
    }

    public function show(int $purchase): JsonResponse
    {
        $model = $this->authorizePurchase($purchase);

        return response()->json(['data' => $model->load('service', 'paymentAttempts')]);
    }

    private function authorizePurchase(int $id)
    {
        return \App\Models\Purchase::query()
            ->whereKey($id)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();
    }
}
