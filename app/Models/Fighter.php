<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fighter extends Model
{
    use HasFactory, HasUuids, ResolvesStorageUrl;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nom',
        'prenom',
        'surnom',
        'weight_category',
        'niveau',
        'reseaux',
        'combat',
        'victoire',
        'defaite',
        'nul',
        'ko',
        'soumission',
        'apropos',
        'style_de_combat',
        'palmares',
        'bio',
        'photo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Ajoute "photo_url" à chaque réponse JSON : c'est elle que le site affiche
    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->storageUrl($this->photo);
    }

    // Les articles qui mentionnent ce fighter
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_fighter');
    }

    protected static function booted(): void
    {
        // Supprimer un fighter supprime aussi son fichier photo
        static::deleting(function (Fighter $fighter) {
            $fighter->deleteStoredFile($fighter->photo);
        });
    }
}
