<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dentist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DentistController extends Controller
{
    /**
     * Display the list of dentists.
     */
    public function index(): View
    {
        return view('admin.dentists.index', [
            'dentists' => Dentist::with('user')
                ->withCount(['appointments as upcoming_count' => function ($query) {
                    $query->where('status', 'confirmed')
                        ->whereDate('scheduled_date', '>=', today()->toDateString());
                }])
                ->orderBy('lastname')
                ->paginate(10),
        ]);
    }

    /**
     * Show the form to add a dentist.
     */
    public function create(): View
    {
        return view('admin.dentists.create', [
            'availableUsers' => User::whereDoesntHave('dentist')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new dentist, optionally linked to a user account.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateDentist($request);

        Dentist::create($data);

        return redirect()
            ->route('admin.dentists.index')
            ->with('success', 'Dentist added successfully.');
    }

    /**
     * Show the form to edit a dentist and manage its user account link.
     */
    public function edit(Dentist $dentist): View
    {
        $dentist->load('user');

        return view('admin.dentists.edit', [
            'dentist' => $dentist,
            'availableUsers' => User::whereDoesntHave('dentist')
                ->orWhereHas('dentist', fn ($query) => $query->whereKey($dentist->id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Update a dentist, including linking a user account.
     */
    public function update(Request $request, Dentist $dentist): RedirectResponse
    {
        $data = $this->validateDentist($request, $dentist);

        $dentist->update($data);

        return redirect()
            ->route('admin.dentists.index')
            ->with('success', 'Dentist updated successfully.');
    }

    /**
     * Remove a dentist. Any linked user account is kept, just unlinked.
     */
    public function destroy(Dentist $dentist): RedirectResponse
    {
        $dentist->delete();

        return redirect()
            ->route('admin.dentists.index')
            ->with('success', 'Dentist removed. Linked future appointments were detached.');
    }

    /**
     * Shared validation rules for dentists.
     */
    private function validateDentist(Request $request, ?Dentist $dentist = null): array
    {
        return $request->validate([
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::unique('dentists', 'user_id')->ignore($dentist?->id),
            ],
            'lastname' => ['required', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'mobile_number' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'license_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dentists', 'license_number')->ignore($dentist?->id),
            ],
            'specialty' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
