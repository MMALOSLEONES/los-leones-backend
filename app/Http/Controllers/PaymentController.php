<?php

namespace App\Http\Controllers;


use App\Models\Demande;
use App\Models\Payment;
use App\Services\PayDunyaService;
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
        protected PayDunyaService $payDunya
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
        ]);

        $demande = Demande::findOrFail($validated['demande_id']);

        if ($demande->status !== 'en_attente_paiement') {
            return response()->json([
                'message' => 'Cette candidature ne peut pas être payée.',
            ], 422);
        }

        $locale = $validated['locale'];

        // Construire les URL de retour et d'annulation
        $returnUrl = url('/payments/return') . '?' . http_build_query([
            'locale' => $locale,
            'demande_id' => $demande->id,
        ]);

        $cancelUrl = url('/payments/cancel') . '?' . http_build_query([
            'locale' => $locale,
            'demande_id' => $demande->id,
        ]);

        // Éviter de créer plusieurs paiements en attente
        $payment = Payment::where('demande_id', $demande->id)
            ->where('statut', 'initie')
            ->latest()
            ->first();

        if (!$payment) {
            $payment = Payment::create([
                'demande_id' => $demande->id,
                'statut' => 'initie',
                'montant' => 10000,
                'fournisseur' => null,
                'passerelle' => 'paydunya',
            ]);
        }

        try {
            $invoice = $this->payDunya->createInvoice([
                'amount' => 10000,
                'description' => 'Frais de candidature MMA Los Leones',
                'customer_name' => trim($demande->prenom . ' ' . $demande->nom),
                'customer_email' => $demande->mail ?? '',
                'customer_phone' => $demande->tel,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ]);

            $payment->update([
                'paydunya_token' => $invoice['token'],
            ]);

            return response()->json([
                'message' => 'Facture créée avec succès.',
                'payment_url' => $invoice['payment_url'],
            ]);
        } catch (Throwable $e) {
            Log::error('Erreur création facture PayDunya', [
                'demande_id' => $demande->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Impossible d\'initialiser le paiement. Réessaie.',
            ], 502);
        }
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
        $token = $request->query('token');

        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        // Si PayDunya ne fournit pas de token, rediriger vers l'annulation.
        if (!$token) {
            return redirect(
                $frontendUrl . "/{$locale}/paiement/annule?" .
                    http_build_query(['demande_id' => $demandeId])
            );
        }

        $payment = Payment::where('paydunya_token', $token)->first();

        if (!$payment) {
            return redirect(
                $frontendUrl . "/{$locale}/paiement/annule?" .
                    http_build_query(['demande_id' => $demandeId])
            );
        }

        try {
            // Vérifier le statut réel auprès de PayDunya.
            $this->synchronizePayment($payment);
        } catch (Throwable $e) {
            Log::error('Erreur vérification retour PayDunya', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Utiliser l'identifiant de la candidature lié au paiement.
        return redirect(
            $frontendUrl . "/{$locale}/paiement/retour?" .
                http_build_query([
                    'demande_id' => $payment->demande_id,
                ])
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

        $frontendUrl = rtrim(config('app.frontend_url'), '/');

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
}
