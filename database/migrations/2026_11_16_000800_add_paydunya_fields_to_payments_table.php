<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('passerelle')
                ->default('paydunya')
                ->after('fournisseur');

            $table->unique('ref_transaction');
            $table->index(['demande_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['ref_transaction']);
            $table->dropIndex(['demande_id', 'statut']);
            $table->dropColumn('passerelle');
        });
    }
};