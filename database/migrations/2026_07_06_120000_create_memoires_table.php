<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memoires', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->foreignId('etudiant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('encadreur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('propose_par_id')->constrained('users')->cascadeOnDelete();
            $table->enum('statut', [
                'propose', 'valide', 'rejete', 'en_cours',
                'corrections_demandees', 'valide_final', 'soutenu',
            ])->default('propose');
            $table->timestamp('date_proposition')->nullable();
            $table->timestamp('date_validation')->nullable();
            $table->text('commentaire_validation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memoires');
    }
};
