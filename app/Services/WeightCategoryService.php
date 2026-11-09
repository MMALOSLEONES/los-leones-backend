<?php

namespace App\Services;

class WeightCategoryService
{
    /** @return array<string, float> */
    public function categories(): array
    {
        return config('weight_categories', []);
    }

    // Catégorie correspondant à un poids. Renvoie null si le poids est inconnu
    // ou au-dessus de la dernière limite : l'admin choisit alors la catégorie à la main.
    public function pourPoids(?float $kg): ?string
    {
        if ($kg === null) {
            return null;
        }

        foreach ($this->categories() as $nom => $limite) {
            if ($kg <= $limite) {
                return $nom;
            }
        }

        return null;
    }

    // Limite haute d'une catégorie (ex : "Welterweight" → 77.1)
    public function limite(?string $categorie): ?float
    {
        if ($categorie === null) {
            return null;
        }

        return $this->categories()[$categorie] ?? null;
    }
}
