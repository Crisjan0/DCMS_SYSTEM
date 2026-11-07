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
        Schema::create('dentists', function (Blueprint $table) {
            $table->id();

            // Connected to the users table (optional until linked by an admin)
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('lastname');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('mobile_number');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('license_number')->unique();
            $table->string('specialty')->nullable();
            $table->string('profile_picture')->nullable();
            $table->enum('role', ['user', 'dentist'])
                ->default('dentist');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentists');
    }
};
