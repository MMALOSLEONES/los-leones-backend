<?php

namespace App\Services;

use App\Models\Demande;
use App\Models\Fighter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class CandidatureService
{
    public function creerCandidature(array $donnees): Demande
    {
        return Demande::create($donnees);
    }
    public function recupererTout()
    {
        return Demande::orderBy('created_at', 'desc')->get();
    }

    public function trouverParId(string $id): Demande
    {
        return Demande::findOrFail($id);
    }
    public function mettreAJour(string $id, array $donnees): Demande
    {
        $candidature = $this->trouverParId($id);


        if (isset($donnees['status']) && $donnees['status'] === 'approuve') {
            $donnees['reviewed_at'] = now();
        }

        $candidature->update($donnees);
        return $candidature;
    }

    public function supprimer(string $id): bool
    {
        $candidature = $this->trouverParId($id);
        return $candidature->delete();
    }
    public function transformerEnFighter(string $id): Fighter
    {
       return DB::transaction(function () use ($id) {
    $candidature = Demande::lockForUpdate()->findOrFail($id);

    if ($candidature->status === 'approuve') {
        abort(409, 'Cette candidature est déjà validée.');
    }

    $fighter = Fighter::create([
        'nom'             => $candidature->nom,
        'prenom'          => $candidature->prenom,
        'niveau'          => $candidature->experience_niveau,
        'photo'           => $this->copierPhoto($candidature->photo),
        'poids_kg'        => $candidature->poids_kg,
        'is_active'       => true,
        'combat'          => $candidature->combats ?? 0,
        'victoire'        => $candidature->victoires ?? 0,
        'defaite'         => $candidature->defaites ?? 0,
        'nul'             => $candidature->nuls ?? 0,
        'ko'              => $candidature->ko ?? 0,
        'soumission'      => $candidature->soumissions ?? 0,
    ]);

    $candidature->update(['status' => 'approuve', 'reviewed_at' => now()]);

    return $fighter;
});

    }

    // Le fighter reçoit SA PROPRE copie du fichier : supprimer la candidature
    // plus tard ne fera pas disparaître la photo du site.
private function copierPhoto(?string $chemin): ?string
{
    if (! $chemin) {
        return null;
    }

    $source = Storage::disk(config('filesystems.private_uploads_disk') ?? 'local');
    $cible = Storage::disk(config('filesystems.uploads_disk') ?? 'public');

    // Vérification de l'existence
    if (! $source->exists($chemin)) {
        return null;
    }

    // Récupérer l'extension de manière sécurisée
    $extension = pathinfo($chemin, PATHINFO_EXTENSION) ?: 'jpg'; // 'jpg' par défaut si Cloudinary ne fournit pas l'extension dans le nom
    $nouveauNom = 'fighters/' . Str::uuid() . '.' . $extension;

    try {
        // En ligne avec Cloudinary, récupérer le contenu brut est beaucoup plus fiable que le readStream
        $contenuFichier = $source->get($chemin);
        
        // On écrit directement le contenu dans le disque cible
        $cible->put($nouveauNom, $contenuFichier);
        
        return $nouveauNom;
    } catch (\Exception $e) {
        // Optionnel : Vous pouvez logguer l'erreur pour voir ce qui bloque précisément en ligne
      \Illuminate\Support\Facades\Log::error("Erreur lors de la copie de la photo sur Cloudinary: " . $e->getMessage());
        return null;
    }
}

}
