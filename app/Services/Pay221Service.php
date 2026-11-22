<?php

namespace App\Services;


use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Pay221Service
{


    public function createPayment(
        Payment $payment,
        string $rail,
        string $returnUrl,
        ?string $phone = null
    ): array {
        if (!in_array($rail, ['sn_wave', 'sn_orange'], true)) {
            throw new RuntimeException('Moyen de paiement invalide.');
        }

        $payload = [
            'amount' => (string) $payment->montant,
            'currency' => 'XOF',
            'rail' => $rail,
            'merchant_reference' => $payment->ref_transaction,
            'return_url' => $returnUrl,
        ];

        if ($phone) {
            $payload['customer_phone'] = $phone;
        }

        $response = Http::baseUrl(config('services.pay221.base_url'))
            ->withToken(config('services.pay221.api_key'))
            ->withHeaders([
                'Idempotency-Key' => (string) $payment->id,
            ])
            ->acceptJson()
            ->timeout(30)
            ->post('/v1/payments', $payload);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Erreur 221 Pay : ' . $response->body()
            );
        }

        $data = $response->json();

        if (empty($data['id']) || empty($data['checkout_url'])) {
            throw new RuntimeException(
                'Réponse 221 Pay incomplète.'
            );
        }

        return $data;
    }



    public function getPayment(string $paymentId): array
    {
        $response = Http::baseUrl(config('services.pay221.base_url'))
            ->withToken(config('services.pay221.api_key'))
            ->acceptJson()
            ->timeout(30)
            ->get('/v1/payments/' . $paymentId);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Impossible de vérifier le paiement : ' . $response->body()
            );
        }

        return $response->json();
    }
}
