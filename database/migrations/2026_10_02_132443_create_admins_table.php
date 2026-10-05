<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public $withinTransaction = false;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('id')->primary()->defaultRaw('gen_random_uuid()');
            $table->string('nom');
            $table->string('prenom');
            $table->string('mail')->unique();
            $table->string('password');
            $table->string('tel')->nullable();

            $table->timestampTz('created_at')->defaultRaw('now()');
            $table->timestampTz('updated_at')->defaultRaw('now()');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
