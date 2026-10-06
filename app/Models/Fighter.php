<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;     

class Fighter extends Model
{
    use HasFactory,HasUuids;

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

    public function fighters()
    {
        return $this->belongsToMany(Fighter::class, 'article_fighter');
    }


    // Force Laravel à traiter "is_active" comme un vrai booléen PHP (true/false)
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
