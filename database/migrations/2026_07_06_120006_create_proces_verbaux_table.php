<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proces_verbaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soutenance_id')->constrained('soutenances')->cascadeOnDelete();
            $table->foreignId('genere_par_id')->constrained('users')->cascadeOnDelete();
            $table->string('fichier');
            $table->timestamp('date_generation');
            $table->text('commentaires')->nullable();
            $table->boolean('est_signe')->default(false);
            $table->timestamp('date_signature')->nullable();
            $table->json('signatures')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proces_verbaux');
    }
};
