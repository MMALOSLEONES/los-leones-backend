<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\WeightCategoryService;

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

    // Ajoute "photo_url" à chaque réponse JSON : c'est elle que le site affiche
    protected $appends = ['photo_url', 'limite_categorie_kg'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->storageUrl($this->photo);
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

        // Supprimer un fighter supprime aussi son fichier photo
        static::deleting(function (Fighter $fighter) {
            $fighter->deleteStoredFile($fighter->photo);
        });
    }
}
