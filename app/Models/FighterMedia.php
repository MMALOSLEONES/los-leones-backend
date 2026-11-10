<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FighterMedia extends Model
{
    use HasUuids, ResolvesStorageUrl;

    protected $table = 'fighter_media';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['fighter_id', 'type', 'chemin', 'mime', 'taille', 'position'];

    // Le JSON contient "url" (utilisable par le navigateur), pas le chemin interne
    protected $hidden = ['chemin'];
    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->storageUrl($this->chemin);
    }

    public function fighter()
    {
        return $this->belongsTo(Fighter::class);
    }

    protected static function booted(): void
    {
        // Supprimer un média supprime aussi son fichier
        static::deleting(function (FighterMedia $media) {
            $media->deleteStoredFile($media->chemin);
        });
    }
}
