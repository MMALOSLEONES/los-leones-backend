<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ajoute le poids aux candidatures et aux fighters.
// La colonne est FACULTATIVE : les anciens dossiers restent valides, sans poids.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['demandes', 'fighters'] as $table) {
            if (Schema::hasColumn($table, 'poids_kg')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->decimal('poids_kg', 4, 1)->nullable();
            });

            // Garde-fou dans la base, en plus de la validation de Laravel
            DB::statement(
                "ALTER TABLE {$table} ADD CONSTRAINT {$table}_poids_kg_check "
                . "CHECK (poids_kg IS NULL OR (poids_kg >= 30 AND poids_kg <= 250))"
            );
        }
    }

    public function down(): void
    {
        foreach (['demandes', 'fighters'] as $table) {
            if (! Schema::hasColumn($table, 'poids_kg')) {
                continue;
            }

            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_poids_kg_check");
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('poids_kg');
            });
        }
    }
};
