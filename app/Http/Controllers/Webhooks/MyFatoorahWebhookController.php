<?php

namespace App\Http\Controllers\Webhooks;


use App\Services\MyFatoorahService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class MyFatoorahWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MyFatoorahService $myFatoorah,
        PaymentService $payments
    ): Response {
        $payload = $request->json()->all();
        $signature = $request->header('myfatoorah-signature');

        abort_unless(
            $myFatoorah->verifyWebhookSignature($payload, $signature),
            401,
            'Invalid MyFatoorah signature.'
        );

        $payments->handleWebhook($payload);

        return response()->noContent();
    }
}
