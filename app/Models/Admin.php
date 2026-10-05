<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// "Authenticatable" permet d'utiliser ce modèle pour connecter l'admin (Login)
class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admins';

    /**
     * 2. LA CLÉ PRIMAIRE (UUID)
     * On désactive l'auto-incrémentation (1, 2, 3...) car c'est un UUID.
     * On précise que la clé est une chaîne de caractères (string).
     */
    public $incrementing = false;
    protected $keyType = 'string';

 
    protected $fillable = [
        'nom',
        'prenom',
        'mail',
        'password', 
        'tel',
      
    ];

    /**
     * 4. LA PROTECTION DES DONNÉES SENSIBLES
     * Quand Laravel va transformer cet Admin en texte (JSON) pour une API ou du JavaScript,
     * il va AUTOMATIQUEMENT masquer le mot de passe pour ne jamais l'exposer.
     */
    protected $hidden = [
        'password',
    ];

    public function demandes() {
    return $this->hasMany(Demande::class, 'reviewed_by');
}


}
