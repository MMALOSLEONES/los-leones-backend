<?php

namespace App\Http\Controllers;

use App\Models\Fighter;
use Illuminate\Http\Request;
use App\Services\FighterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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

    public function destroy(string $id)
    {
        Fighter::findOrFail($id)->delete();
        return response()->json(['message' => 'Fighter supprimé.']);
    }

    public function index(): JsonResponse
    {
        // Récupère uniquement les combattants actifs et triés par nouveauté
        // Vous pouvez ajouter une condition comme : ->where('is_purchasable', true) si vous avez ce champ
        $fighters = Fighter::where('is_active', true)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fighters
        ], 200);
    }

    /**
     * Afficher les détails d'un combattant spécifique de la boutique.
     * Route : GET /api/store/fighters/{id}
     */
    public function show(string $id): JsonResponse
    {
        $fighter = Fighter::where('is_active', true)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fighter
        ], 200);
    }

    // Ajoute OU remplace la photo d'un fighter
    public function updatePhoto(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 Mo
        ]);

        $fighter = Fighter::findOrFail($id);
        $disk = config('filesystems.uploads_disk') ?? 'public';

        $ancien = $fighter->photo;
        $fighter->photo = $request->file('photo')->store('fighters', $disk);
        $fighter->save();

        // L'ancien fichier n'est supprimé qu'une fois le nouveau bien enregistré
        if ($ancien && ! str_starts_with($ancien, 'http')) {
            Storage::disk($disk)->delete($ancien);
        }

        return response()->json(['message' => 'Photo mise à jour.', 'data' => $fighter], 200);
    }
}
