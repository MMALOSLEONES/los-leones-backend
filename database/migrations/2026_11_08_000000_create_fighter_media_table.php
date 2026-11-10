<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Photos et vidéos d'un fighter : une ligne par fichier, avec un ordre.
// La première photo (position la plus basse) est la photo de couverture.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fighter_media')) {
            return;
        }

        Schema::create('fighter_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fighter_id')->constrained('fighters')->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('chemin');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('taille')->nullable(); // en octets
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['fighter_id', 'type', 'position']);
        });

        DB::statement("ALTER TABLE fighter_media ADD CONSTRAINT fighter_media_type_check CHECK (type IN ('photo', 'video'))");

        // Les photos actuelles (colonne "photo" des fighters) deviennent la photo de couverture.
        // On les DÉPLACE : la colonne est vidée ensuite, pour ne pas avoir deux sources.
        DB::table('fighters')->whereNotNull('photo')->orderBy('id')->each(function ($fighter) {
            DB::table('fighter_media')->insert([
                'id' => (string) Str::uuid(),
                'fighter_id' => $fighter->id,
                'type' => 'photo',
                'chemin' => $fighter->photo,
                'position' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::table('fighters')->whereNotNull('photo')->update(['photo' => null]);
    }

    public function down(): void
    {
        // On remet la couverture dans l'ancienne colonne avant de supprimer la table
        if (Schema::hasTable('fighter_media')) {
            DB::table('fighter_media')->where('type', 'photo')->where('position', 0)->orderBy('id')->each(function ($media) {
                DB::table('fighters')->where('id', $media->fighter_id)->update(['photo' => $media->chemin]);
            });
        }

        Schema::dropIfExists('fighter_media');
    }
};
