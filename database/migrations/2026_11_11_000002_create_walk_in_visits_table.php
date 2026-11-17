<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Walk-in visits: unbooked patients the clinic served on the spot.
     * Counted into daily_clinic_stats.total_visits so the forecasting
     * model sees the clinic's TRUE daily volume.
     */
    public function up(): void
    {
        Schema::create('walk_in_visits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('dentists')->nullOnDelete();
            $table->string('service');                  // from Appointment::SERVICES
            $table->date('visit_date');
            $table->unsignedInteger('count')->default(1); // patient count logged
            $table->string('notes', 500)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['visit_date']);
        });

        Schema::table('daily_clinic_stats', function (Blueprint $table) {
            $table->unsignedInteger('walk_ins')->default(0)->after('total_visits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_clinic_stats', function (Blueprint $table) {
            $table->dropColumn('walk_ins');
        });

        Schema::dropIfExists('walk_in_visits');
    }
};
