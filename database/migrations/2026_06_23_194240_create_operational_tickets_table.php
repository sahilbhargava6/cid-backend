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
        Schema::create('operational_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_type'); // tax_prep, bookkeeping, solar, small_business, procurement
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->decimal('price', 10, 2)->nullable();
            $table->json('input_parameters')->nullable(); // holds service-specific dynamic data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_tickets');
    }
};
