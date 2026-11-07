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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('scheduled_date')->index();
            $table->time('scheduled_time')->nullable();
            $table->string('service')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
                ->default('pending')
                ->index();

            $table->timestamps();

            // Speeds up the time-series aggregation (GROUP BY date WHERE status)
            $table->index(['scheduled_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
