<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'titre',
        'contenu',
        'date_publication',
        'status',
    ];

    protected $casts = [
        'date_publication' => 'datetime',
    ];

    // Les photos sont toujours chargées avec l'article (liste, détail, admin) :
    // aucune route ni service à modifier pour qu'elles apparaissent dans le JSON.
    protected $with = ['photos'];

    // La première photo (position la plus basse) sert de couverture
    public function photos()
    {
        return $this->hasMany(ArticlePhoto::class)->orderBy('position');
    }

    // Les fighters mentionnés dans l'article
    // (dans ta version, cette relation s'appelait articles() et renvoyait des Article)
    public function fighters()
    {
        return $this->belongsToMany(Fighter::class, 'article_fighter');
    }

    protected static function booted(): void
    {
        // La base supprime les lignes de photos toute seule, mais pas les fichiers :
        // on passe par chaque photo pour que son fichier soit effacé aussi.
        static::deleting(function (Article $article) {
            $article->photos->each->delete();
        });
    }
}
