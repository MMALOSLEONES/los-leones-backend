<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 

class Demande extends Model
{
    use HasFactory,HasUuids;

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
        'a_experience_combat',
        'experience_niveau',
        'annee_experience',
        'motivation',
        'photo',
        'comment_connu',
        'status',
        'reviewed_at'
    ];
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
