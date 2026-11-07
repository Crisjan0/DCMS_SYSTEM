<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        // ── Demo accounts: admin / staff / doctors ──
        // Role is stored on the linked patient profile (per clinic schema).
        $accounts = [
            ['name' => 'Clinic Admin', 'email' => 'admin@dcms.test', 'role' => 'admin', 'lastname' => 'Administrator', 'firstname' => 'Clinic', 'gender' => 'male'],
            ['name' => 'Reception Staff', 'email' => 'staff@dcms.test', 'role' => 'staff', 'lastname' => 'Santos', 'firstname' => 'Maria', 'gender' => 'female'],
            ['name' => 'Dr. Ana Reyes', 'email' => 'dr.reyes@dcms.test', 'role' => 'doctor', 'lastname' => 'Reyes', 'firstname' => 'Ana', 'gender' => 'female'],
            ['name' => 'Dr. Carlo Cruz', 'email' => 'dr.cruz@dcms.test', 'role' => 'doctor', 'lastname' => 'Cruz', 'firstname' => 'Carlo', 'gender' => 'male'],
        ];

        foreach ($accounts as $account) {
            $user = User::create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => 'password',
            ]);

            Patient::create([
                'user_id' => $user->id,
                'lastname' => $account['lastname'],
                'firstname' => $account['firstname'],
                'middlename' => fake()->lastName(),
                'date_of_birth' => fake()->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
                'mobile_number' => fake()->numerify('09#########'),
                'gender' => $account['gender'],
                'civil_status' => fake()->randomElement(['single', 'married']),
                'address' => fake()->streetAddress().', Cebu City',
                'occupation' => 'Clinic Personnel',
                'patient_type' => 'regular',
                'profile_picture' => null,
                'role' => $account['role'],
            ]);
        }

        // ── 40 regular patients that generate the historical visits ──
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
