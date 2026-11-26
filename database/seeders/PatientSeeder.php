<?php

namespace Database\Seeders;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        // ── Clinic admin: a user account with the is_admin flag (no patient profile) ──
        User::create([
            'name' => 'Clinic Admin',
            'email' => 'admin@dcms.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        // ── Demo account: reception staff (patient profile with role 'staff') ──
        $staff = User::create([
            'name' => 'Reception Staff',
            'email' => 'staff@dcms.test',
            'password' => 'password',
        ]);

        Patient::create([
            'user_id' => $staff->id,
            'lastname' => 'Santos',
            'firstname' => 'Maria',
            'middlename' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
            'mobile_number' => fake()->numerify('09#########'),
            'gender' => 'female',
            'civil_status' => fake()->randomElement(['single', 'married']),
            'address' => fake()->streetAddress().', Cebu City',
            'occupation' => 'Clinic Personnel',
            'patient_type' => 'regular',
            'profile_picture' => null,
            'role' => 'staff',
        ]);

        // ── Dentist accounts with their own dentists profile table ──
        $dentists = [
            ['name' => 'Dr. Ana Reyes', 'email' => 'dr.reyes@dcms.test', 'lastname' => 'Reyes', 'firstname' => 'Ana', 'gender' => 'female', 'license' => 'DMD-00001', 'specialty' => 'General Dentistry'],
            ['name' => 'Dr. Carlo Cruz', 'email' => 'dr.cruz@dcms.test', 'lastname' => 'Cruz', 'firstname' => 'Carlo', 'gender' => 'male', 'license' => 'DMD-00002', 'specialty' => 'Orthodontics'],
        ];

        foreach ($dentists as $dentist) {
            $user = User::create([
                'name' => $dentist['name'],
                'email' => $dentist['email'],
                'password' => 'password',
            ]);

            Dentist::create([
                'user_id' => $user->id,
                'lastname' => $dentist['lastname'],
                'firstname' => $dentist['firstname'],
                'middlename' => fake()->lastName(),
                'date_of_birth' => fake()->dateTimeBetween('-60 years', '-30 years')->format('Y-m-d'),
                'mobile_number' => fake()->numerify('09#########'),
                'gender' => $dentist['gender'],
                'license_number' => $dentist['license'],
                'specialty' => $dentist['specialty'],
                'profile_picture' => null,
            ]);
        }

        // ── Regular patients that generate the historical visits ──
        for ($i = 1; $i <= 40; $i++) {
            $gender = fake()->randomElement(['male', 'female']);
            $firstname = fake()->firstName($gender === 'female' ? 'female' : 'male');
            $lastname = fake()->lastName();

            $user = User::create([
                'name' => $firstname.' '.$lastname,
                'email' => sprintf('patient%03d@dcms.test', $i),
                'password' => 'password',
            ]);

            Patient::create([
                'user_id' => $user->id,
                'lastname' => $lastname,
                'firstname' => $firstname,
                'middlename' => fake()->lastName(),
                'date_of_birth' => fake()->dateTimeBetween('-70 years', '-15 years')->format('Y-m-d'),
                'mobile_number' => fake()->numerify('09#########'),
                'gender' => $gender,
                'civil_status' => fake()->randomElement(['single', 'single', 'married', 'widowed', 'separated', 'divorced']),
                'address' => fake()->streetAddress().', Cebu City',
                'occupation' => fake()->randomElement(['Teacher', 'Driver', 'Student', 'Nurse', 'Vendor', 'Engineer', 'Housewife', 'Call Center Agent', 'Office Worker']),
                'patient_type' => fake()->randomElement(['new', 'returning']),
                'profile_picture' => null,
                'role' => 'patient',
            ]);
        }
    }
}
