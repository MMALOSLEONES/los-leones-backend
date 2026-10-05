<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
     public $withinTransaction = false;
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->uuid('id')->primary()->defaultRaw('gen_random_uuid()');
            $table->string('reference_code')->unique()->nullable(); // Géré par le trigger
            $table->string('nom');
            $table->string('prenom');
            $table->string('tel');
            $table->string('mail')->nullable();
            $table->date('date_naissance');
            $table->string('ville')->nullable();
            $table->boolean('a_experience_combat')->default(false);
            $table->enum('experience_niveau', ['debutant', 'intermediaire', 'avance', 'professionnel'])->nullable();
            $table->integer('annee_experience')->nullable();
            $table->text('motivation')->nullable();
            $table->string('photo')->nullable();
            $table->enum('comment_connu', ['instagram', 'tiktok', 'facebook', 'whatsapp', 'recommandation', 'google', 'autre'])->nullable();
            $table->enum('status', ['en_attente_paiement', 'paye', 'approuve', 'refuse'])->default('en_attente_paiement');
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('created_at')->defaultRaw('now()');
            $table->timestampTz('updated_at')->defaultRaw('now()');
            
            $table->foreignUuid('reviewed_by')
                ->nullable() // Nullable car au début, aucun admin n'a encore traité la demande
                ->constrained('admins') // Pointe vers la table 'admins'
                ->onDelete('set null'); // Si l'admin est supprimé, on garde la demande mais le champ devient NULL


            // Ajout des index présents dans votre SQL
            $table->index('status', 'idx_demande_status');
            $table->index('reference_code', 'idx_demande_reference_code');
        });

        // Injection SQL brute de votre Sequence + Trigger pour le reference_code
        DB::statement('CREATE SEQUENCE IF NOT EXISTS demande_ref_seq START 1;');
        DB::statement("
            CREATE OR REPLACE FUNCTION generate_demande_reference()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.reference_code := 'LL-' || EXTRACT(YEAR FROM now()) || '-' || LPAD(nextval('demande_ref_seq')::text, 5, '0');
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");
        DB::statement("
            CREATE TRIGGER trg_demande_reference
            BEFORE INSERT ON demandes
            FOR EACH ROW
            EXECUTE FUNCTION generate_demande_reference();
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_demande_reference ON demandes;');
        DB::statement('DROP FUNCTION IF EXISTS generate_demande_reference();');
        DB::statement('DROP SEQUENCE IF EXISTS demande_ref_seq;');
        Schema::dropIfExists('demandes');
    }
};
