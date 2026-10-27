<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ajoute à "demandes" les colonnes que le formulaire collecte déjà.
// Chaque colonne est testée avant d'être créée : la migration ne plante pas
// si l'une d'elles existe déjà.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            if (! Schema::hasColumn('demandes', 'quartier')) {
                $table->string('quartier')->nullable();
            }
            if (! Schema::hasColumn('demandes', 'a_fait_competitions')) {
                $table->boolean('a_fait_competitions')->nullable();
            }
            foreach (['nombre_combats', 'victoires', 'defaites', 'nuls', 'kos', 'soumissions'] as $colonne) {
                if (! Schema::hasColumn('demandes', $colonne)) {
                    $table->unsignedSmallInteger($colonne)->nullable();
                }
            }
            if (! Schema::hasColumn('demandes', 'consent_accepted')) {
                $table->boolean('consent_accepted')->default(false);
            }
            if (! Schema::hasColumn('demandes', 'consent_accepted_at')) {
                $table->timestampTz('consent_accepted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropColumn([
                'quartier', 'a_fait_competitions', 'nombre_combats', 'victoires',
                'defaites', 'nuls', 'kos', 'soumissions', 'consent_accepted', 'consent_accepted_at',
            ]);
        });
    }
};
