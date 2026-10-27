<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

// Partagé par Demande, Fighter et ArticlePhoto : transforme le chemin enregistré
// en base ("fighters/abc.jpg") en adresse utilisable par le navigateur.
trait ResolvesStorageUrl
{
    protected function uploadsDisk(): string
    {
        return config('filesystems.uploads_disk') ?? 'public';
    }

    protected function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Déjà une adresse complète (stockage externe) : rien à transformer
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Cette ligne de type règle la "ligne rouge" de l'éditeur : Storage::disk() est
        // déclaré comme renvoyant l'interface Filesystem, qui n'a pas de méthode url().
        // À l'exécution, c'est bien un FilesystemAdapter, donc tout fonctionne.
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->uploadsDisk());

        return $disk->url($path);
    }

    protected function deleteStoredFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk($this->uploadsDisk())->delete($path);
        }
    }
}
