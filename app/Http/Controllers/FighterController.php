<?php

namespace App\Http\Controllers;

use App\Models\Fighter;
use Illuminate\Http\Request;
use App\Services\FighterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FighterController extends Controller
{
    protected $fighterService;

    public function __construct(FighterService $fighterService)
    {
        $this->fighterService = $fighterService;
    }

    /**
     * Route ADMIN : Création directe d'un fighter
     * POST /api/admin/fighters
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
            'is_active' => 'boolean',
            'poids_kg' => ['nullable', 'numeric', 'between:30,250'],
        ]);

        $fighter = $this->fighterService->creerFighter($donneesValidees);

        return response()->json([
            'message' => 'Combattant créé avec succès !',
            'data' => $fighter
        ], 201);
    }

    /**
     * Route ADMIN : Supprimer un fighter
     * DELETE /api/admin/fighters/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        Fighter::findOrFail($id)->delete();
        return response()->json(['message' => 'Fighter supprimé.']);
    }

    /**
     * Route ADMIN : Liste TOUS les fighters (actifs ET masqués)
     * GET /api/admin/fighters
     */
    public function index(): JsonResponse
    {
        $fighters = Fighter::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $fighters
        ], 200);
    }

    /**
     * Route ADMIN : Détails de n'importe quel fighter (actif ou masqué)
     * GET /api/admin/fighters/{id}
     */
    public function show(string $id): JsonResponse
    {
        $fighter = Fighter::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fighter
        ], 200);
    }

    /**
     * Route PUBLIQUE : Liste UNIQUEMENT les fighters visibles sur le site
     * GET /api/fighters
     */
    public function publicIndex(): JsonResponse
    {
        $fighters = Fighter::where('is_active', true)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fighters
        ], 200);
    }

    /**
     * Route PUBLIQUE : Détails d'un fighter visible uniquement
     * GET /api/fighters/{id}
     */
    public function publicShow(string $id): JsonResponse
    {
        $fighter = Fighter::where('is_active', true)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fighter
        ], 200);
    }

    /**
     * Route ADMIN/COMMUN : Modifier les infos d'un fighter (gère le bouton is_active)
     * PUT /api/admin/fighters/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'prenom' => ['sometimes', 'required', 'string', 'max:255'],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'surnom' => ['nullable', 'string', 'max:255'],
            'weight_category' => ['nullable'],
            'poids_kg' => ['nullable', 'numeric', 'between:30,250'],
            'niveau' => ['nullable', 'in:debutant,intermediaire,avance,professionnel'],
            'reseaux' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'apropos' => ['nullable', 'string'],
            'style_de_combat' => ['nullable', 'string'],
            'palmares' => ['nullable', 'string'],
            'victoire' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'defaite' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'nul' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'ko' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'soumission' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'], // Gère la case cochée ou non
        ]);

        $fighter = Fighter::findOrFail($id);
        $fighter->fill($data);
        
        // Calcul automatique du total des combats
        $fighter->combat = ($fighter->victoire ?? 0) + ($fighter->defaite ?? 0) + ($fighter->nul ?? 0);
        $fighter->save();

        return response()->json(['message' => 'Fighter mis à jour.', 'data' => $fighter->fresh()]);
    }

    /**
     * Route ADMIN : Ajoute ou remplace la photo d'un fighter
     * POST /api/admin/fighters/{id}/photo
     */
    public function updatePhoto(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $fighter = Fighter::findOrFail($id);
        $disk = config('filesystems.uploads_disk') ?? 'public';

        $ancien = $fighter->photo;
        $fighter->photo = $request->file('photo')->store('fighters', $disk);
        $fighter->save();

        if ($ancien && ! str_starts_with($ancien, 'http')) {
            Storage::disk($disk)->delete($ancien);
        }

        return response()->json(['message' => 'Photo mise à jour.', 'data' => $fighter], 200);
    }
}
