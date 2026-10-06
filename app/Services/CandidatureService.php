<?php

namespace App\Services;

use App\Models\Demande;
use App\Models\Fighter;
use Illuminate\Support\Facades\DB;


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

        /**
     * Extraire les données d'une candidature approuvée pour créer un Fighter.
     */
    public function transformerEnFighter(string $id): Fighter
    {
        // En entreprise, on utilise une transaction de base de données (DB::transaction) 
        // pour s'assurer que si la création du Fighter plante, la candidature ne change pas de statut.
        return DB::transaction(function () use ($id) {
            
            // 1. Récupérer la candidature ou renvoyer une erreur 404
            $candidature = $this->trouverParId($id);

            // 2. Mettre à jour le statut de la candidature
            $candidature->update([
                'status' => 'approuve',
                'reviewed_at' => now(),
            ]);

            // 3. Extraire et copier les données de la Demande vers la table Fighters sur Neon
            // Rappelez-vous : 'demande_id' sera votre future clé relationnelle si vous l'ajoutez plus tard !
            $fighter = Fighter::create([
                'nom' => $candidature->nom,
                'prenom' => $candidature->prenom,
                'niveau' => $candidature->experience_niveau,
                'photo' => $candidature->photo,
                'is_active' => true,
                'combat' => 0,
                'victoire' => 0,
                'defaite' => 0,
                'nul' => 0,
                'ko' => 0,
                'soumission' => 0,
            ]);

            return $fighter;
        });
    }

}
