<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

// Copie les photos existantes vers les disques actuellement configurés.
//   Étape 1 (local)  : les photos de candidats passent du disque public au disque privé.
//   Étape 2 (Cloudflare R2) : tout part vers les buckets.
// La commande peut être relancée sans risque : un fichier déjà copié est ignoré.
// Elle ne supprime JAMAIS la source : tu le fais toi-même, après vérification.
class MigrerPhotos extends Command
{
    protected $signature = 'photos:migrer {--source=public : Disque où se trouvent les fichiers actuels}';

    protected $description = 'Copie les photos existantes vers les disques configurés (publics et privés)';

    public function handle(): int
    {
        $source = $this->option('source');

        // Dossier → disque de destination
        $cibles = [
            'fighters' => config('filesystems.uploads_disk') ?? 'public',
            'articles' => config('filesystems.uploads_disk') ?? 'public',
            'candidatures' => config('filesystems.private_uploads_disk') ?? 'local',
        ];

        $copies = 0;
        $ignores = 0;
        $erreurs = 0;

        foreach ($cibles as $dossier => $disqueCible) {
            if ($disqueCible === $source) {
                $this->line("• {$dossier} : déjà sur « {$source} », rien à faire.");
                continue;
            }

            $this->info("• {$dossier} : « {$source} » → « {$disqueCible} »");
            $depuis = Storage::disk($source);
            $vers = Storage::disk($disqueCible);

            foreach ($depuis->allFiles($dossier) as $chemin) {
                if ($vers->exists($chemin)) {
                    $ignores++;
                    continue;
                }

                $flux = null;
                try {
                    $flux = $depuis->readStream($chemin);
                    $vers->writeStream($chemin, $flux);
                    $copies++;
                    $this->line("  copié : {$chemin}");
                } catch (\Throwable $e) {
                    $erreurs++;
                    $this->error("  ÉCHEC : {$chemin} ({$e->getMessage()})");
                } finally {
                    if (is_resource($flux)) {
                        fclose($flux);
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Terminé : {$copies} copié(s), {$ignores} déjà présent(s), {$erreurs} erreur(s).");

        return $erreurs > 0 ? self::FAILURE : self::SUCCESS;
    }
}
