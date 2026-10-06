<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * Route publique : GET /api/articles (Tout afficher)
     */
    public function index(): JsonResponse
    {
        $articles = $this->articleService->recupererTout();
        return response()->json($articles, 200);
    }

    /**
     * Route publique : GET /api/articles/{id} (Voir un article complet)
     */
    public function show(string $id): JsonResponse
    {
        $article = $this->articleService->trouverParId($id);
        return response()->json($article, 200);
    }

    /**
     * Route admin : POST /api/admin/articles (Créer)
     */
    public function store(Request $request): JsonResponse
    {
        $donneesValidees = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'date_publication' => 'nullable|date',
            'status' => 'required|in:draft,publie',
        ]);

        $article = $this->articleService->creer($donneesValidees);

        return response()->json([
            'message' => 'Article enregistré avec succès.',
            'data' => $article
        ], 201);
    }

    /**
     * Route admin : PUT /api/admin/articles/{id} (Modifier)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $donneesValidees = $request->validate([
            'titre' => 'nullable|string|max:255',
            'contenu' => 'nullable|string',
            'date_publication' => 'nullable|date',
            'status' => 'nullable|in:draft,publie',
        ]);

        $article = $this->articleService->mettreAJour($id, $donneesValidees);

        return response()->json([
            'message' => 'Article mis à jour avec succès.',
            'data' => $article
        ], 200);
    }

    /**
     * Route admin : DELETE /api/admin/articles/{id} (Supprimer)
     */
    public function destroy(string $id): JsonResponse
    {
        $this->articleService->supprimer($id);

        return response()->json([
            'message' => 'Article supprimé définitivement.'
        ], 200);
    }
}
