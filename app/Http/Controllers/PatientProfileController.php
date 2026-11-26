<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PatientProfileController extends Controller
{
    /**
     * Update the logged-in patient's own record (personal information + profile picture)
     * from the profile page modal.
     */
    public function update(Request $request): RedirectResponse
    {
        $patient = $request->user()->patient;

        if (! $patient) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'This account has no patient record to update.');
        }

        $validated = $request->validateWithBag('patientUpdate', [
            'lastname' => ['required', 'string', 'max:100'],
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'civil_status' => ['required', Rule::in(['single', 'married', 'widowed', 'separated', 'divorced'])],
            'address' => ['required', 'string', 'max:500'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'patient_type' => ['required', Rule::in(['regular', 'new', 'returning'])],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // 'role' is deliberately excluded — patients cannot change their own role.
        ]);

        $picture = $validated['profile_picture'] ?? null;
        unset($validated['profile_picture']);

        $patient->fill($validated);

        if ($picture) {
            // Replace the old photo (and clean it from disk).
            if ($patient->profile_picture) {
                Storage::disk('public')->delete($patient->profile_picture);
            }

            $patient->profile_picture = $picture->store('patient-pictures', 'public');
        }

        $patient->save();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'personal-info-updated');
    }
}
