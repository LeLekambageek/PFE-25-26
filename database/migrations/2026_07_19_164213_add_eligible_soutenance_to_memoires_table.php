<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('memoires', function (Blueprint $table) {
            $table->boolean('eligible_soutenance')->default(false)->after('statut');
            $table->timestamp('date_eligibilite_soutenance')->nullable()->after('eligible_soutenance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memoires', function (Blueprint $table) {
            $table->dropColumn(['eligible_soutenance', 'date_eligibilite_soutenance']);
        });
    }
};
