<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DemandeMedia extends Model
{
    use HasUuids, ResolvesStorageUrl;

    protected $table = 'demande_media';

    protected $fillable = ['demande_id', 'type', 'chemin', 'position'];

    protected $appends = ['url'];

    // CORRECTION POUR LE STOCKAGE EN LIGNE (R2 PRIVÉ) :
    public function getUrlAttribute(): ?string
    {
        if (!$this->chemin) {
            return null;
        }

        // Si le chemin commence déjà par http, on le renvoie tel quel
        if (str_starts_with($this->chemin, 'http')) {
            return $this->chemin;
        }

        // On force Laravel à lire sur le disque privé (r2_private) configuré dans votre config/filesystems.php
        $diskPrive = config('filesystems.private_uploads_disk') ?? 'r2_private';
        
        return Storage::disk($diskPrive)->url($this->chemin);
    }

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }
}
