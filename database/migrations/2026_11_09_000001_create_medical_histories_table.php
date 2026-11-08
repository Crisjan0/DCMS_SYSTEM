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
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();

            // One health declaration per patient.
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('allergies')->nullable();    // e.g. "Penicillin, Latex"
            $table->string('medications')->nullable();  // e.g. "Metformin 500mg"

            $table->boolean('hypertension')->default(false);
            $table->boolean('heart_disease')->default(false);
            $table->boolean('diabetes')->default(false);
            $table->boolean('asthma')->default(false);
            $table->boolean('kidney_disease')->default(false);
            $table->boolean('liver_disease')->default(false);
            $table->boolean('blood_disorder')->default(false);
            $table->boolean('epilepsy')->default(false);
            $table->boolean('pregnant')->default(false);
            $table->boolean('smoker')->default(false);

            $table->text('other_conditions')->nullable();
            $table->text('medical_notes')->nullable();

            // Who last updated the declaration (patient or clinic staff).
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('declared_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
