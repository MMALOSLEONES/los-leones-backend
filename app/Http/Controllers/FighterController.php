<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FighterService;
use Illuminate\Http\JsonResponse;

class FighterController extends Controller
{
    protected $fighterService;

    public function __construct(FighterService $fighterService)
    {
        $this->fighterService = $fighterService;
    }

    /**
     * Route : POST /api/admin/fighters (Création directe)
     */
    public function store(Request $request): JsonResponse
    {
        $donneesValidees = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'surnom' => 'nullable|string|max:255',
            'weight_category' => 'nullable|string|max:255',
            'niveau' => 'nullable|in:debutant,intermediaire,avance,professionnel',
            'reseaux' => 'nullable|string',
            'combat' => 'integer|min:0',
            'victoire' => 'integer|min:0',
            'defaite' => 'integer|min:0',
            'nul' => 'integer|min:0',
            'ko' => 'integer|min:0',
            'soumission' => 'integer|min:0',
            'apropos' => 'nullable|string',
            'style_de_combat' => 'nullable|string',
            'palmares' => 'nullable|string',
            'bio' => 'nullable|string',
            'photo' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $fighter = $this->fighterService->creerFighter($donneesValidees);

        return response()->json([
            'message' => 'Combattant créé avec succès !',
            'data' => $fighter
        ], 201);
    }
}
