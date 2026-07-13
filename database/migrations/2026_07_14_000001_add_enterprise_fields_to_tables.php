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
        Schema::table('operational_tickets', function (Blueprint $table) {
            $table->string('timezone')->nullable()->default('UTC');
            $table->enum('milestone', ['Drafting', 'Review', 'Filing', 'Completed'])->default('Drafting');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('category')->nullable()->default('other');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_tickets', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'milestone']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
