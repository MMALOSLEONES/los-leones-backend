<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Désactive l'auto-incrémentation car la clé est un UUID
    public $incrementing = false;
    protected $keyType = 'string';

    // Liste des colonnes modifiables via Eloquent (hors clés relationnelles)
    protected $fillable = [
        'statut',
        'montant',
        'fournisseur',
        'ref_transaction',
        'confirmed_at',
    ];

    // Indique à Laravel que ce champ est une date/timestamp
    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }
}
