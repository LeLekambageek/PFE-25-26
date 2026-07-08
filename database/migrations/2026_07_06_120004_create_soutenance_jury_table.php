<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soutenance_jury', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soutenance_id')->constrained('soutenances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_jury', ['president', 'rapporteur', 'examinateur']);
            $table->boolean('convocation_envoyee')->default(false);
            $table->timestamp('convoque_a')->nullable();
            $table->timestamps();

            $table->unique(['soutenance_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soutenance_jury');
    }
};
