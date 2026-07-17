<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('titre');
            $table->text('message');
            $table->json('data')->nullable();
            $table->enum('statut', ['non_lue', 'lue'])->default('non_lue');
            $table->timestamp('date_creation')->default(now());
            $table->timestamp('date_lecture')->nullable();
            $table->morphs('notifiable');
            $table->timestamps();

            $table->index(['user_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
