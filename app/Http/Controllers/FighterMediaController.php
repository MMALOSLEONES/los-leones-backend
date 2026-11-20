<?php

namespace App\Http\Controllers;

use App\Models\Fighter;
use App\Models\FighterMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Photos et vidéos des fighters (routes admin).
// Photos : elles passent par le site puis Laravel (déjà réduites par le navigateur).
// Vidéos : Laravel signe une adresse temporaire et le NAVIGATEUR envoie le fichier
// directement à Cloudflare R2.
class FighterMediaController extends Controller
{
    private const MAX_PHOTOS = 10;
    private const MAX_VIDEOS = 5;
    private const MAX_VIDEO_BYTES = 150 * 1024 * 1024; // 150 Mo
    // .mov = les vidéos d'iPhone (video/quicktime)
    private const VIDEO_TYPES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];

    private function disqueNom(): string
    {
        return config('filesystems.uploads_disk') ?? 'public';
    }

    // Comptages via le modèle et non via la relation (triée) : PostgreSQL refuse
    // un tri dans une requête d'agrégat.
    private function nombre(string $fighterId, string $type): int
    {
        return FighterMedia::where('fighter_id', $fighterId)->where('type', $type)->count();
    }

    private function prochainePosition(string $fighterId, string $type): int
    {
        $max = FighterMedia::where('fighter_id', $fighterId)->where('type', $type)->max('position');

        return $max === null ? 0 : ((int) $max) + 1;
    }

    // POST /admin/fighters/{id}/photos : ajoute UNE photo
    public function addPhoto(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $fighter = Fighter::findOrFail($id);

        if ($this->nombre($fighter->id, 'photo') >= self::MAX_PHOTOS) {
            return response()->json(['message' => 'Un fighter ne peut pas avoir plus de ' . self::MAX_PHOTOS . ' photos.'], 422);
        }

        $fichier = $request->file('photo');
        $media = $fighter->media()->create([
            'type' => 'photo',
            'chemin' => $fichier->store('fighters', $this->disqueNom()),
            'mime' => $fichier->getMimeType(),
            'taille' => $fichier->getSize(),
            'position' => $this->prochainePosition($fighter->id, 'photo'),
        ]);

        return response()->json(['message' => 'Photo ajoutée.', 'data' => $media], 201);
    }

    // POST /admin/fighters/{id}/videos/upload-url
    public function videoUploadUrl(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'content_type' => ['required', 'string', 'in:' . implode(',', array_keys(self::VIDEO_TYPES))],
            'size' => ['required', 'integer', 'min:1', 'max:' . self::MAX_VIDEO_BYTES],
        ]);

        $fighter = Fighter::findOrFail($id);

        if ($this->nombre($fighter->id, 'video') >= self::MAX_VIDEOS) {
            return response()->json(['message' => 'Un fighter ne peut pas avoir plus de ' . self::MAX_VIDEOS . ' vidéos.'], 422);
        }

        // Seul un disque qui n'est pas du S3 (le disque local, en développement) passe par Laravel.
        // Si le disque EST du S3, une erreur (clés, endpoint…) doit se voir, pas se cacher.
        if (config('filesystems.disks.' . $this->disqueNom() . '.driver') !== 's3') {
            return response()->json(['mode' => 'server', 'chemin' => null]);
        }

        $chemin = 'fighters/videos/' . Str::uuid() . '.' . self::VIDEO_TYPES[$data['content_type']];

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disque */
        $disque = Storage::disk($this->disqueNom());

        $cible = $disque->temporaryUploadUrl(
            $chemin,
            now()->addMinutes(15),
            ['ContentType' => $data['content_type']]
        );

        return response()->json([
            'mode' => 'direct',
            'url' => $cible['url'],
            'headers' => $cible['headers'],
            'chemin' => $chemin,
        ]);
    }

    // POST /admin/fighters/{id}/videos
    // Le navigateur a envoyé le fichier à R2 et le déclare ici : on VÉRIFIE qu'il existe.
    public function registerVideo(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'chemin' => ['required', 'string', 'regex:#^fighters/videos/[0-9a-f\-]{36}\.(mp4|webm|mov)$#'],
        ]);

        $fighter = Fighter::findOrFail($id);
        $chemin = $data['chemin'];
        $disque = Storage::disk($this->disqueNom());

        if (! $disque->exists($chemin)) {
            return response()->json(['message' => "Le fichier n'a pas été reçu : l'envoi n'a pas abouti."], 422);
        }

        if (FighterMedia::where('chemin', $chemin)->exists()) {
            return response()->json(['message' => 'Cette vidéo est déjà enregistrée.'], 422);
        }

        $taille = $disque->size($chemin);
        $mime = $disque->mimeType($chemin);

        if ($taille > self::MAX_VIDEO_BYTES || ! array_key_exists((string) $mime, self::VIDEO_TYPES)) {
            $disque->delete($chemin);

            return response()->json(['message' => 'Fichier refusé : vidéo MP4, MOV ou WebM de 150 Mo maximum.'], 422);
        }

        if ($this->nombre($fighter->id, 'video') >= self::MAX_VIDEOS) {
            $disque->delete($chemin);

            return response()->json(['message' => 'Un fighter ne peut pas avoir plus de ' . self::MAX_VIDEOS . ' vidéos.'], 422);
        }

        $media = $fighter->media()->create([
            'type' => 'video',
            'chemin' => $chemin,
            'mime' => $mime,
            'taille' => $taille,
            'position' => $this->prochainePosition($fighter->id, 'video'),
        ]);

        return response()->json(['message' => 'Vidéo ajoutée.', 'data' => $media], 201);
    }

    // POST /admin/fighters/{id}/videos/file
    // DÉVELOPPEMENT LOCAL UNIQUEMENT : sans R2, le fichier passe par Laravel.
    public function addVideoFile(Request $request, string $id): JsonResponse
    {
        abort_unless(app()->environment('local'), 404);

        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:153600'], // 150 Mo en Ko
        ]);

        $fighter = Fighter::findOrFail($id);

        if ($this->nombre($fighter->id, 'video') >= self::MAX_VIDEOS) {
            return response()->json(['message' => 'Un fighter ne peut pas avoir plus de ' . self::MAX_VIDEOS . ' vidéos.'], 422);
        }

        $fichier = $request->file('video');
        $media = $fighter->media()->create([
            'type' => 'video',
            'chemin' => $fichier->store('fighters/videos', $this->disqueNom()),
            'mime' => $fichier->getMimeType(),
            'taille' => $fichier->getSize(),
            'position' => $this->prochainePosition($fighter->id, 'video'),
        ]);

        return response()->json(['message' => 'Vidéo ajoutée.', 'data' => $media], 201);
    }

    // PUT /admin/fighters/{id}/media/{mediaId}/cover : met une photo en première position
    public function makeCover(string $id, string $mediaId): JsonResponse
    {
        $photo = FighterMedia::where('fighter_id', $id)->where('type', 'photo')->findOrFail($mediaId);

        DB::transaction(function () use ($id, $photo) {
            $autres = FighterMedia::where('fighter_id', $id)
                ->where('type', 'photo')
                ->where('id', '!=', $photo->id)
                ->orderBy('position')
                ->get();

            $photo->update(['position' => 0]);

            foreach ($autres->values() as $index => $autre) {
                $autre->update(['position' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Photo de couverture mise à jour.']);
    }

    // DELETE /admin/fighters/{id}/media/{mediaId}
    public function destroy(string $id, string $mediaId): JsonResponse
    {
        $media = FighterMedia::where('fighter_id', $id)->findOrFail($mediaId);
        $media->delete();

        return response()->json(['message' => 'Média supprimé.']);
    }
}