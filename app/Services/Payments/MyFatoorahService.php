<?php

namespace App\Services\Payments;

use App\Models\Purchase;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MyFatoorahService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.myfatoorah.test_mode')
            ? config('services.myfatoorah.test_url')
            : config('services.myfatoorah.live_url');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->withToken(config('services.myfatoorah.api_key'))
            ->timeout(30);
    }

    public function initiatePayment(
        Purchase $purchase,
        int $paymentMethodId
    ): array {
        $payload = [
            'InvoiceValue' => (float) $purchase->amount,

            'PaymentMethodId' => $paymentMethodId,

            'CustomerName' => $purchase->user->name,

            'CustomerEmail' => $purchase->user->email,

            'CustomerReference' => (string) $purchase->id,

            'UserDefinedField' => (string) $purchase->id,

            'Language' => 'EN',

            'CallBackUrl' => config('services.myfatoorah.success_url'),

            'ErrorUrl' => config('services.myfatoorah.error_url'),

            'WebhookUrl' => config('services.myfatoorah.webhook_url'),
        ];

        $response = $this->client()
            ->post('/v2/ExecutePayment', $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('Message')
                ?? 'MyFatoorah payment request failed.'
            );
        }

        $data = $response->json('Data');

        if (!$data) {
            throw new RuntimeException(
                'Invalid response received from MyFatoorah.'
            );
        }

        return $data;
    }

    public function getPaymentStatus(
        string $key,
        string $keyType = 'PaymentId'
    ): array {
        $response = $this->client()
            ->post('/v2/GetPaymentStatus', [
                'Key' => $key,
                'KeyType' => $keyType,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('Message')
                ?? 'Unable to retrieve MyFatoorah payment status.'
            );
        }

        return $response->json('Data');
    }
}
