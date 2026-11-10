<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\WeightCategoryService;
use Illuminate\Validation\Rule;

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
        'poids_kg'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'poids_kg' => 'float'
    ];

    // Les photos et vidéos sont toujours chargées avec le fighter
    protected $with = ['media'];

    // Ajoute "photo_url" à chaque réponse JSON : c'est elle que le site affiche
    protected $appends = ['photo_url', 'limite_categorie_kg'];

    // Photo de couverture : la première photo. L'ancienne colonne "photo" sert de secours.
    public function getPhotoUrlAttribute(): ?string
    {
        $couverture = $this->media->firstWhere('type', 'photo');

        return $couverture?->url ?? $this->storageUrl($this->photo);
    }

    // Limite haute de la catégorie (ex : Welterweight → 77) : le site l'affiche sous le nom
    public function getLimiteCategorieKgAttribute(): ?int
    {
        $limite = app(WeightCategoryService::class)->limite($this->weight_category);

        return $limite !== null ? (int) round($limite) : null;
    }

    // Les articles qui mentionnent ce fighter
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_fighter');
    }

    protected static function booted(): void
    {
        // Catégorie automatique : un poids est connu et aucune catégorie n'a été choisie
        static::saving(function (Fighter $fighter) {
            if ($fighter->poids_kg !== null && empty($fighter->weight_category)) {
                $fighter->weight_category = app(WeightCategoryService::class)
                    ->pourPoids((float) $fighter->poids_kg);
            }
        });

        // Supprimer un fighter supprime aussi ses fichiers
        static::deleting(function (Fighter $fighter) {
            $fighter->media->each->delete();
            $fighter->deleteStoredFile($fighter->photo); // ancienne colonne, par sécurité
        });
    }

    // Photos et vidéos, dans l'ordre
    public function media()
    {
        return $this->hasMany(FighterMedia::class)->orderBy('position');
    }
}
