<?php

namespace App\Services;

use App\Models\Fighter;

class FighterService
{
    public function creerFighter(array $donnees): Fighter
    {
        return Fighter::create($donnees);
    }
}
