<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MyFatoorahService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('myfatoorah.base_url'), '/');
        $this->token = config('myfatoorah.api_key');
    }

    public function createPayment(array $payload): array
    {
        return $this->request('POST', '/v3/payments', $payload);
    }

    public function getPaymentDetails(string $paymentId): array
    {
        return $this->request('GET', '/v3/payments/'.urlencode($paymentId));
    }

    public function verifyWebhookSignature(array $payload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $data = data_get($payload, 'Data', []);

        $values = [
            'Invoice.Id' => data_get($data, 'Invoice.Id'),
            'Invoice.Status' => data_get($data, 'Invoice.Status'),
            'Transaction.Status' => data_get($data, 'Transaction.Status'),
            'Transaction.PaymentId' => data_get($data, 'Transaction.PaymentId'),
            'Invoice.ExternalIdentifier' => data_get($data, 'Invoice.ExternalIdentifier'),
        ];

        $message = collect($values)
            ->map(fn ($value, $key) => $key.'='.(is_null($value) ? '' : $value))
            ->implode(',');

        $expected = base64_encode(
            hash_hmac('sha256', $message, config('myfatoorah.webhook_secret'), true)
        );

        return hash_equals($expected, $signature);
    }

    private function request(string $method, string $uri, array $payload = []): array
    {
        $request = Http::acceptJson()
            ->withToken($this->token)
            ->timeout(config('myfatoorah.timeout', 30))
            ->retry(3, 500);

        $response = $method === 'GET'
            ? $request->get($this->baseUrl.$uri)
            : $request->post($this->baseUrl.$uri, $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'MyFatoorah API error: '.$response->status().' '.$response->body()
            );
        }

        $json = $response->json();

        if (($json['IsSuccess'] ?? true) === false) {
            throw new RuntimeException($json['Message'] ?? 'MyFatoorah request failed.');
        }

        return $json;
    }
}
