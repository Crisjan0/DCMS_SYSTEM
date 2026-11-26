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
        Schema::create('treatment_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('dentists')->nullOnDelete();
            // Optional link to the visit this treatment was charted from.
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();

            $table->date('treatment_date')->index();
            // FDI tooth number, when the procedure targets a single tooth.
            $table->string('tooth_number', 2)->nullable();

            $table->string('procedure');                // e.g. "Composite Filling"
            $table->string('diagnosis')->nullable();
            $table->text('treatment_notes')->nullable();
            $table->date('follow_up_date')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['patient_id', 'treatment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_records');
    }
};
