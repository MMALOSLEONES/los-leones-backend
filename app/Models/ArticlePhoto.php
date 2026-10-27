<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArticlePhoto extends Model
{
    use HasUuids, ResolvesStorageUrl;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['article_id', 'chemin', 'position'];

    // Le JSON contient "url" (utilisable par le navigateur), pas le chemin interne
    protected $appends = ['url'];
    protected $hidden = ['chemin'];

    public function getUrlAttribute(): ?string
    {
        return $this->storageUrl($this->chemin);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (ArticlePhoto $photo) {
            $photo->deleteStoredFile($photo->chemin);
        });
    }
}
