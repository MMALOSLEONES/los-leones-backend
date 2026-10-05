<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleFighter extends Model
{
    use HasFactory;

    // On force Laravel à chercher le nom exact au singulier/sans pluriel automatique
    protected $table = 'article_fighter';

    public $incrementing = false;
    protected $keyType = 'string';

    // Pour le moment vide, accueillera vos futures clés étrangères
    protected $fillable = [];
}
