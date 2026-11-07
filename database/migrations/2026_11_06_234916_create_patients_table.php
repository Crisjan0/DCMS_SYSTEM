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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Connected to the users table
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('lastname');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->date('date_of_birth');
            $table->string('mobile_number');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('civil_status', ['single', 'married', 'widowed', 'separated', 'divorced']);
            $table->text('address');
            $table->string('occupation')->nullable();
            $table->string('patient_type');
            $table->string('profile_picture')->nullable();
            $table->enum('role', ['patient', 'staff'])
                ->default('patient');

            $table->timestamps();
        });
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
