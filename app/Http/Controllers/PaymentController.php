<?php

namespace App\Http\Controllers;


use App\Models\Demande;
use App\Models\Payment;
use App\Services\Pay221Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        protected Pay221Service $pay221
    ) {}

    /**
     * Initialiser un paiement pour une candidature.
     */
    /**
     * Initialiser un paiement pour une candidature.
     */

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'demande_id' => ['required', 'uuid', 'exists:demandes,id'],
            'locale' => ['required', 'in:fr,en'],
            'rail' => ['required', 'in:sn_wave,sn_orange'],
        ]);

        $demande = Demande::findOrFail($validated['demande_id']);

        if ($demande->status !== 'en_attente_paiement') {
            return response()->json([
                'message' => 'Cette candidature ne peut pas être payée.',
            ], 422);
        }

        $rail = $validated['rail'];

        $fournisseur = $rail === 'sn_wave'
            ? 'wave'
            : 'orange_money';

        $locale = $validated['locale'];

        $frontendUrl = rtrim(config('services.frontend_url'), '/');

        if (!$frontendUrl || !str_starts_with($frontendUrl, 'https://')) {
            return response()->json([
                'message' => 'URL du frontend invalide.',
            ], 500);
        }

        $returnUrl = $frontendUrl . "/{$locale}/paiement/retour?" .
            http_build_query([
                'demande_id' => $demande->id,
            ]);

        try {
            // Réutiliser une tentative en attente pour éviter les doublons.
            $payment = Payment::where('demande_id', $demande->id)
                ->where('passerelle', '221pay')
                ->where('fournisseur', $fournisseur)
                ->where('statut', 'initie')
                ->latest()
                ->first();

            if (!$payment) {
                $payment = Payment::create([
                    'demande_id' => $demande->id,
                    'statut' => 'initie',
                    'montant' => 10000,
                    'fournisseur' => $fournisseur,
                    'passerelle' => '221pay',
                    'ref_transaction' => 'LL-' . Str::uuid(),
                ]);
            }

            $result = $this->pay221->createPayment(
                $payment,
                $rail,
                $returnUrl,
                $demande->tel
            );

            $payment->update([
                'pay221_payment_id' => $result['id'],
            ]);

            return response()->json([
                'message' => 'Paiement initialisé avec succès.',
                'payment_url' => $result['checkout_url'],
            ]);
        } catch (Throwable $e) {
            Log::error('Erreur création paiement 221 Pay', [
                'demande_id' => $demande->id,
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Impossible d\'initialiser le paiement. Réessaie.',
            ], 502);
        }
    }


    private function synchronize221Payment(Payment $payment): void
    {
        if (!$payment->pay221_payment_id) {
            throw new RuntimeException('Identifiant 221 Pay manquant.');
        }

        $result = $this->pay221->getPayment(
            $payment->pay221_payment_id
        );

        $status = strtolower($result['status'] ?? '');

        DB::transaction(function () use ($payment, $status) {
            $lockedPayment = Payment::whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $demande = Demande::whereKey($lockedPayment->demande_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Un paiement confirmé ne doit jamais être rétrogradé.
            if ($lockedPayment->statut === 'reussi') {
                return;
            }

            if ($status === 'confirmed') {
                $lockedPayment->update([
                    'statut' => 'reussi',
                    'confirmed_at' => now(),
                ]);

                if ($demande->status === 'en_attente_paiement') {
                    $demande->update([
                        'status' => 'paye',
                    ]);
                }
            } elseif (in_array($status, ['failed', 'expired'], true)) {
                $lockedPayment->update([
                    'statut' => 'echoue',
                ]);
            }
        });
    }


    /**
     * Retour du candidat après paiement.
     */
    /**
     * Retour du candidat après paiement.
     */

    public function handleReturn(Request $request)
    {
        $locale = in_array($request->query('locale'), ['fr', 'en'], true)
            ? $request->query('locale')
            : 'fr';

        $demandeId = $request->query('demande_id');

        $frontendUrl = rtrim(config('services.frontend_url'), '/');

        if (!$demandeId) {
            return redirect($frontendUrl . "/{$locale}/paiement/annule");
        }

        $payment = Payment::where('demande_id', $demandeId)
            ->where('passerelle', '221pay')
            ->latest()
            ->first();

        if ($payment && $payment->pay221_payment_id) {
            try {
                $this->synchronize221Payment($payment);
            } catch (Throwable $e) {
                Log::error('Erreur vérification retour 221 Pay', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect(
            $frontendUrl . "/{$locale}/paiement/retour?" .
                http_build_query(['demande_id' => $demandeId])
        );
    }


    /**
     * Annulation du paiement.
     */
    /**
     * Annulation du paiement.
     */
    public function handleCancel(Request $request)
    {
        $locale = in_array($request->query('locale'), ['fr', 'en'], true)
            ? $request->query('locale')
            : 'fr';

        $demandeId = $request->query('demande_id');

        $frontendUrl = rtrim(config('services.frontend_url'), '/');

        return redirect(
            $frontendUrl . "/{$locale}/paiement/annule?" .
                http_build_query([
                    'demande_id' => $demandeId,
                ])
        );
    }
    /**
     * Notification IPN de PayDunya.
     */
    public function ipn(Request $request): JsonResponse
    {
        $data = $request->input('data');

        if (!is_array($data)) {
            return response()->json([
                'message' => 'Données IPN invalides.',
            ], 400);
        }

        $receivedHash = $data['hash'] ?? null;
        $masterKey = config('services.paydunya.master_key');

        if (
            !$receivedHash ||
            !$masterKey ||
            !hash_equals(hash('sha512', $masterKey), $receivedHash)
        ) {
            return response()->json([
                'message' => 'Signature invalide.',
            ], 403);
        }

        $token = $data['invoice']['token'] ?? null;

        if (!$token) {
            return response()->json([
                'message' => 'Token manquant.',
            ], 400);
        }

        $payment = Payment::where('paydunya_token', $token)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Paiement introuvable.',
            ], 404);
        }

        try {
            $this->synchronizePayment($payment);

            return response()->json([
                'message' => 'Notification traitée.',
            ]);
        } catch (Throwable $e) {
            Log::error('Erreur IPN PayDunya', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur de traitement.',
            ], 500);
        }
    }

    /**
     * Vérifier le statut du paiement d'une candidature.
     */
    public function status(string $demandeId): JsonResponse
    {
        $demande = Demande::find($demandeId);

        if (!$demande) {
            return response()->json([
                'message' => 'Candidature introuvable.',
            ], 404);
        }

        // Un paiement réussi reste prioritaire sur les tentatives suivantes.
        $paiementReussi = $demande->payments()
            ->where('statut', 'reussi')
            ->exists();

        if ($paiementReussi) {
            return response()->json([
                'payment_status' => 'reussi',
                'confirmed' => true,
            ]);
        }

        $dernierPaiement = $demande->payments()
            ->latest('created_at')
            ->first();

        if (!$dernierPaiement) {
            return response()->json([
                'payment_status' => 'absent',
                'confirmed' => false,
            ]);
        }

        return response()->json([
            'payment_status' => $dernierPaiement->statut,
            'confirmed' => false,
        ]);
    }

    /**
     * Vérifier le statut réel auprès de PayDunya
     * et synchroniser les données locales.
     */
    private function synchronizePayment(Payment $payment): void
    {
        $invoice = $this->payDunya->confirmInvoice(
            $payment->paydunya_token
        );

        $status = strtolower(
            $invoice['status'] ?? $invoice['invoice']['status'] ?? ''
        );

        DB::transaction(function () use ($payment, $status, $invoice) {
            $lockedPayment = Payment::whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $demande = Demande::whereKey($lockedPayment->demande_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($status === 'completed') {
                if ($lockedPayment->statut !== 'reussi') {
                    $lockedPayment->update([
                        'statut' => 'reussi',
                        'confirmed_at' => now(),
                    ]);
                }

                if ($demande->status === 'en_attente_paiement') {
                    $demande->update([
                        'status' => 'paye',
                    ]);
                }
            } elseif (in_array($status, ['failed', 'cancelled'], true)) {
                if ($lockedPayment->statut !== 'reussi') {
                    $lockedPayment->update([
                        'statut' => 'echoue',
                    ]);
                }
            }
        });
    }



    public function webhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        $timestamp = (string) $request->header('X-221-Timestamp');
        $signature = (string) $request->header('X-221-Signature');
        $secret = config('services.pay221.webhook_secret');

        if (!$secret || !ctype_digit($timestamp) || !$signature) {
            return response()->json(['message' => 'Signature invalide.'], 400);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json(['message' => 'Notification expirée.'], 400);
        }

        $expected = 'v1=' . hash_hmac(
            'sha256',
            $timestamp . '.' . $rawBody,
            $secret
        );

        if (!hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Signature invalide.'], 400);
        }

        $event = json_decode($rawBody, true);

        if (!is_array($event) || empty($event['id']) || empty($event['type'])) {
            return response()->json(['message' => 'Événement invalide.'], 400);
        }

        // Événement de test envoyé depuis le tableau de bord 221 Pay.
        if ($event['type'] === 'test.ping') {
            return response()->json(['message' => 'Webhook de test reçu.']);
        }

        if (
            !str_starts_with($event['type'], 'payment.') ||
            empty($event['object_id'])
        ) {
            return response()->json(['message' => 'Événement ignoré.']);
        }

        try {
            // Relire le paiement auprès de 221 Pay : son API fait foi.
            $result = $this->pay221->getPayment($event['object_id']);

            $payment = Payment::where('pay221_payment_id', $event['object_id'])
                ->first();

            if (!$payment) {
                return response()->json(['message' => 'Paiement introuvable.'], 404);
            }

            $status = strtolower($result['status'] ?? '');

            DB::transaction(function () use ($event, $payment, $status) {
                // L'identifiant unique empêche de traiter deux fois le même événement.
                $inserted = DB::table('pay221_webhook_events')->insertOrIgnore([
                    'id' => $event['id'],
                    'type' => $event['type'],
                    'processed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (!$inserted) {
                    return;
                }

                $lockedPayment = Payment::whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $demande = Demande::whereKey($lockedPayment->demande_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPayment->statut === 'reussi') {
                    return;
                }

                if ($status === 'confirmed') {
                    $lockedPayment->update([
                        'statut' => 'reussi',
                        'confirmed_at' => now(),
                    ]);

                    if ($demande->status === 'en_attente_paiement') {
                        $demande->update(['status' => 'paye']);
                    }
                } elseif (in_array($status, ['failed', 'expired'], true)) {
                    $lockedPayment->update([
                        'statut' => 'echoue',
                    ]);
                }
            });

            return response()->json(['message' => 'Notification traitée.']);
        } catch (Throwable $e) {
            Log::error('Erreur webhook 221 Pay', [
                'event_id' => $event['id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur de traitement.',
            ], 500);
        }
    }
}
