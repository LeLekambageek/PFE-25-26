<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soutenance_jury', function (Blueprint $table) {
            $table->boolean('notes_validees')->default(false);
            $table->timestamp('notes_validees_a')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('soutenance_jury', function (Blueprint $table) {
            $table->dropColumn([
                'notes_validees',
                'notes_validees_a',
            ]);
        });
    }
};
