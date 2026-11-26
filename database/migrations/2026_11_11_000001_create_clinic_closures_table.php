<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clinic closure calendar: public holidays, branch closures,
     * maintenance days. Feeds booking validation and the
     * forecasting module (closed days forecast as zero).
     */
    public function up(): void
    {
        Schema::create('clinic_closures', function (Blueprint $table) {
            $table->id();

            $table->date('date')->unique();             // the closed day
            $table->string('name', 150);                // e.g. "Christmas Day"
            $table->string('type', 20)->default('holiday'); // holiday | closure | maintenance

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_closures');
    }
};
