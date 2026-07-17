<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creneaux_soutenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planifie_par_id')->constrained('users')->cascadeOnDelete();
            $table->date('date_disponible');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('salle');
            $table->enum('statut', ['disponible', 'reserve', 'annule'])->default('disponible');
            $table->foreignId('soutenance_id')->nullable()->constrained('soutenances')->nullOnDelete();
            $table->foreignId('memoire_id')->nullable()->constrained('memoires')->nullOnDelete();
            $table->timestamp('date_reservation')->nullable();
            $table->timestamps();

            $table->index(['date_disponible', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creneaux_soutenance');
    }
};
