<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DemandeMedia extends Model
{
    use HasUuids, ResolvesStorageUrl;

    protected $table = 'demande_media';

    protected $fillable = ['demande_id', 'type', 'chemin', 'position'];

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->storageUrl($this->chemin);
    }

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }
}
