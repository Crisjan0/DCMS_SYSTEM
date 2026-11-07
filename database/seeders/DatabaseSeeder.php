<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PatientSeeder::class,   // demo admin/staff/doctors + 40 patients
            AppointmentSeeder::class, // 3 years of clinic history
        ]);
    }
}
