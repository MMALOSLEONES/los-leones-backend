<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Demande extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reference_code',
        'nom',
        'prenom',
        'tel',
        'mail',
        'date_naissance',
        'ville',
        'quartier',
        'a_experience_combat',
        'experience_niveau',
        'annee_experience',
        'a_fait_competitions',
        'nombre_combats',
        'victoires',
        'defaites',
        'nuls',
        'kos',
        'soumissions',
        'motivation',
        'photo',
        'comment_connu',
        'consent_accepted',
        'consent_accepted_at',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'a_experience_combat' => 'boolean',
        'a_fait_competitions' => 'boolean',
        'consent_accepted' => 'boolean',
        'consent_accepted_at' => 'datetime',
    ];

    // La photo d'un candidat est une donnée personnelle : elle n'a JAMAIS d'adresse publique.
    // Le JSON indique seulement si une photo existe ; l'admin la récupère via
    // GET /admin/candidatures/{id}/photo, qui exige d'être connecté.
    protected $hidden = ['photo'];
    protected $appends = ['has_photo'];

    public function getHasPhotoAttribute(): bool
    {
        return ! empty($this->photo);
    }

    protected static function booted(): void
    {
        // Supprimer une candidature supprime aussi la photo du candidat
        static::deleting(function (Demande $demande) {
            if ($demande->photo) {
                Storage::disk(config('filesystems.private_uploads_disk') ?? 'local')->delete($demande->photo);
            }
        });
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
