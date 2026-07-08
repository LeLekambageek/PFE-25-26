<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memoire_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memoire_id')->constrained('memoires')->cascadeOnDelete();
            $table->foreignId('soumis_par_id')->constrained('users')->cascadeOnDelete();
            $table->enum('numero_version', ['v1', 'v2', 'v3', 'finale'])->default('v1');
            $table->string('fichier_path');
            $table->string('fichier_nom_original')->nullable();
            $table->enum('statut', ['en_attente', 'corrige', 'valide'])->default('en_attente');
            $table->timestamps();

            $table->unique(['memoire_id', 'numero_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memoire_versions');
    }
};
