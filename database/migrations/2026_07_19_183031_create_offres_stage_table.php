<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offres_stage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->text('competences_requises')->nullable();
            $table->date('date_debut_souhaitee')->nullable();
            $table->date('date_fin_souhaitee')->nullable();
            $table->enum('statut', ['ouverte', 'fermee'])->default('ouverte');
            $table->foreignId('publiee_par_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offres_stage');
    }
};