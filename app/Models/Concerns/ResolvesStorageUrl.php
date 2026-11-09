<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

// Partagé par Demande, Fighter et ArticlePhoto : transforme le chemin enregistré
// en base ("fighters/abc.jpg") en adresse utilisable par le navigateur.
trait ResolvesStorageUrl
{
    protected function uploadsDisk(): string
    {
        // Utilise la clé définie dans le .env (r2_public), sinon se rabat sur 'public'
        return config('filesystems.uploads_disk') ?? 'public';
    }

    protected function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Déjà une adresse complète (stockage externe direct) : rien à transformer
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->uploadsDisk());

        // Correction pour Cloudflare R2 : Si le disque est local ou public classique, 
        // ou si l'URL générée ne contient pas déjà le protocole http complet, 
        // on s'assure de renvoyer l'URL publique absolue.
        $url = $disk->url($path);

        // Si l'URL renvoyée est un chemin relatif (ex: /storage/fighters/abc.jpg) 
        // et qu'on utilise R2 en production, on force l'utilisation de l'URL du Bucket
        if (!str_starts_with($url, 'http') && $this->uploadsDisk() === 'r2_public') {
            $publicUrl = rtrim(env('R2_PUBLIC_URL'), '/');
            return $publicUrl . '/' . ltrim($path, '/');
        }

        return $url;
    }

    protected function deleteStoredFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk($this->uploadsDisk())->delete($path);
        }
    }
}
