<?php

namespace App\Http\Controllers\Admin;


use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Purchase::query()->with(['user', 'service', 'paymentAttempts']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        return response()->json($query->latest()->paginate(25));
    }

    public function show(Purchase $purchase): JsonResponse
    {
        return response()->json([
            'data' => $purchase->load(['user', 'service', 'paymentAttempts.events']),
        ]);
    }
}
