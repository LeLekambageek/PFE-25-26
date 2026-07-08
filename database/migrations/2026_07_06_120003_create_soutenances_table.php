<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soutenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memoire_id')->unique()->constrained('memoires')->cascadeOnDelete();
            $table->foreignId('planifiee_par_id')->constrained('users')->cascadeOnDelete();
            $table->date('date_soutenance');
            $table->time('heure_debut');
            $table->string('salle');
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->decimal('note_finale', 4, 2)->nullable();
            $table->string('mention')->nullable();
            $table->string('pv_path')->nullable();
            $table->boolean('resultats_publies')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soutenances');
    }
};
