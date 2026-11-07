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
        Schema::create('forecast_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('forecast_run_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();

            $table->float('predicted');
            $table->float('lower')->nullable();   // confidence band
            $table->float('upper')->nullable();

            $table->boolean('is_peak')->default(false); // seasonal spike day

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_points');
    }
};
