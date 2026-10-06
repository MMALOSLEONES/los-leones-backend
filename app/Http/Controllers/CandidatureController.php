<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CandidatureService;
use Illuminate\Http\JsonResponse;

class CandidatureController extends Controller
{
    protected $candidatureService;

    public function __construct(CandidatureService $candidatureService)
    {
        $this->candidatureService = $candidatureService;
    }
    public function store(Request $request): JsonResponse
    {
        $donneesValidees = $request->validate([

            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'tel' => 'required|string|max:50',
            'date_naissance' => 'required|date',

            'mail' => 'nullable|email|max:255',
            'ville' => 'nullable|string|max:255',
            'motivation' => 'nullable|string',
            'photo' => 'nullable|string', // URL ou chemin de la photo
            'annee_experience' => 'nullable|integer|min:0',


            'a_experience_combat' => 'boolean',

            'experience_niveau' => 'nullable|in:debutant,intermediaire,avance,professionnel',
            'comment_connu' => 'nullable|in:instagram,tiktok,facebook,whatsapp,recommandation,google,autre',
            'status' => 'string|in:en_attente_paiement,paye,approuve,refuse',

        ]);


        $candidature = $this->candidatureService->creerCandidature($donneesValidees);

        // Étape 3 : Réponse HTTP
        return response()->json([
            'message' => 'Candidature enregistrée avec succès !',
            'data' => $candidature
        ], 201);
    }


      public function index(): JsonResponse
    {
        $candidatures = $this->candidatureService->recupererTout();
        
        return response()->json($candidatures, 200);
    }

    public function show(string $id): JsonResponse
    {
        $candidature = $this->candidatureService->trouverParId($id);
        
        return response()->json($candidature, 200);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        // On ne valide que ce que l'admin a le droit de modifier à ce stade
        $donneesValidees = $request->validate([
            'status' => 'nullable|in:en_attente_paiement,paye,approuve,refuse',
            'experience_niveau' => 'nullable|in:debutant,intermediaire,avance,professionnel',
        ]);

        $candidature = $this->candidatureService->mettreAJour($id, $donneesValidees);

        return response()->json([
            'message' => 'Candidature mise à jour avec succès',
            'data' => $candidature
        ], 200);
    }

       public function destroy(string $id): JsonResponse
    {
        $this->candidatureService->supprimer($id);

        return response()->json([
            'message' => 'Candidature supprimée définitivement.'
        ], 200);
    }

     public function promouvoirEnFighter(string $id): JsonResponse
    {
        $fighter = $this->candidatureService->transformerEnFighter($id);

        return response()->json([
            'message' => 'La candidature a été validée et le combattant a été créé avec succès !',
            'fighter' => $fighter
        ], 201);
    }
}
