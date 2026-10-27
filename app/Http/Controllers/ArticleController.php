<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;
use App\Models\ArticlePhoto;

class ArticleController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }
    public function index(): JsonResponse
    {
        $articles = Article::where('status', 'publie')
            ->whereNotNull('date_publication')
            ->where('date_publication', '<=', now())
            ->orderByDesc('date_publication')
            ->get();

        return response()->json($articles, 200);
    }

    public function show(string $id): JsonResponse
    {
        $article = Article::where('status', 'publie')->findOrFail($id);

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
    public function indexAdmin()
    {
        return Article::latest()->get();
    }


    // Ajoute UNE photo à un article (le dashboard les envoie une par une)
    public function addPhoto(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $article = Article::findOrFail($id);

        if ($article->photos()->count() >= 10) {
            return response()->json(['message' => 'Un article ne peut pas avoir plus de 10 photos.'], 422);
        }

        $photo = $article->photos()->create([
            'chemin' => $request->file('photo')->store('articles', config('filesystems.uploads_disk') ?? 'public'),
            'position' => ($article->photos()->max('position') ?? -1) + 1,
        ]);

        return response()->json(['message' => 'Photo ajoutée.', 'data' => $photo], 201);
    }

    public function removePhoto(string $id, string $photoId): JsonResponse
    {
        $photo = ArticlePhoto::where('article_id', $id)->findOrFail($photoId);
        $photo->delete(); // le fichier est supprimé par le modèle (évènement "deleting")

        return response()->json(['message' => 'Photo supprimée.']);
    }
}
