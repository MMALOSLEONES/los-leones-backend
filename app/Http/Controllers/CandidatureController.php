<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CandidatureService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreCandidatureRequest;
use App\Models\Demande;
use Illuminate\Support\Facades\Storage;

class CandidatureController extends Controller
{
    protected $candidatureService;

    public function __construct(CandidatureService $candidatureService)
    {
        $this->candidatureService = $candidatureService;
    }

    /**
     * POST /api/candidatures - Enregistrement global de l'onboarding
     */
    public function store(StoreCandidatureRequest $request): JsonResponse
    {
        // 1. On sépare les champs de texte des tableaux de fichiers pour le nettoyage
        $donnees = $request->safe()->except(['photo', 'photos', 'videos']);
        $donnees['consent_accepted_at'] = now();
        $donnees['status'] = 'en_attente_paiement'; // jamais fourni par le visiteur

        $diskPrive = config('filesystems.private_uploads_disk') ?? 'local';

        // 2. CORRECTION : Extraction sécurisée du premier fichier du tableau pour l'ancienne colonne de secours
        if ($request->hasFile('photos')) {
            $toutesLesPhotos = $request->file('photos');
            // reset() permet d'isoler proprement le premier fichier physique (Couverture)
            $premierePhoto = reset($toutesLesPhotos); 
            
            if ($premierePhoto) {
                $donnees['photo'] = $premierePhoto->store('candidatures', $diskPrive);
            }
        }

        // 3. Création initiale de la demande d'inscription
        $candidature = $this->candidatureService->creerCandidature($donnees);

        // 4. Traitement et stockage du lot complet de PHOTOS (Max 10)
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photoFile) {
                // Stockage physique sur le bucket privé R2 ou local
                $chemin = $photoFile->store('candidatures/photos', $diskPrive);

                // Association du média à la candidature
                $candidature->media()->create([
                    'type'     => 'photo',
                    'chemin'   => $chemin,
                    'position' => $index,
                ]);
            }
        }

        // 5. Traitement et stockage du lot complet de VIDÉOS (Max 5)
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $index => $videoFile) {
                // Stockage physique sur le bucket privé R2 ou local
                $chemin = $videoFile->store('candidatures/videos', $diskPrive);

                // Association du média à la candidature
                $candidature->media()->create([
                    'type'     => 'video',
                    'chemin'   => $chemin,
                    'position' => $index,
                ]);
            }
        }

        // On recharge la relation multimédia pour renvoyer un objet complet au navigateur
        return response()->json([
            'message' => 'Candidature enregistrée avec succès !', 
            'data'    => $candidature->load('media')
        ], 201);
    }

    public function index(): JsonResponse
    {
        $candidatures = $this->candidatureService->recupererTout();

        return response()->json($candidatures, 200);
    }

    public function show(string $id): JsonResponse
    {
        $candidature = $this->candidatureService->trouverParId($id)->load('media');

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
            'data' => $candidature->load('media')
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

    // Photo d'un candidat : réservée à l'admin connecté
    public function photo(string $id)
    {
        $demande = Demande::findOrFail($id);
        abort_unless($demande->photo, 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(config('filesystems.private_uploads_disk') ?? 'local');
        abort_unless($disk->exists($demande->photo), 404);

        // Servie à travers Laravel : le fichier n'a jamais d'adresse publique
        return $disk->response($demande->photo, null, ['Cache-Control' => 'private, max-age=300']);
    }
}
