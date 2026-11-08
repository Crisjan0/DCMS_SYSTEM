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
        Schema::create('tooth_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();

            // FDI two-digit numbering ("quadrant tooth", e.g. 11, 28, 36, 48).
            $table->string('tooth_number', 2);

            // Rows only exist for teeth needing attention / with work done;
            // marking a tooth as "healthy" deletes its row.
            $table->enum('status', [
                'caries',
                'filled',
                'crown',
                'root_canal',
                'missing',
                'implant',
                'bridge',
                'denture',
            ])->default('caries');

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['patient_id', 'tooth_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tooth_records');
    }
};
