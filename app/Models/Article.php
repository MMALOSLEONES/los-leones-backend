<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

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

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_fighter');
    }
}
