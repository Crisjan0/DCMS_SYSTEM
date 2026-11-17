<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ClinicClosure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Clinic closure calendar: public holidays, branch closures and
 * maintenance days. Blocked in booking, respected by the forecast.
 */
class ClinicClosureController extends Controller
{
    public function index(): View
    {
        return view('clinic.closures.index', [
            'upcoming' => ClinicClosure::query()->upcoming()->get(),
            'past' => ClinicClosure::query()
                ->where('date', '<', today())
                ->orderByDesc('date')
                ->limit(10)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today', 'unique:clinic_closures,date'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(ClinicClosure::TYPES)],
        ]);

        ClinicClosure::create($data);

        return back()->with('success', 'Clinic closure saved: '.$data['name'].' on '.$data['date'].'. Booking is now blocked and the forecast will predict zero visits that day.');
    }

    public function destroy(ClinicClosure $closure): RedirectResponse
    {
        $closure->delete();

        return back()->with('success', 'Clinic closure removed — '.$closure->date->format('M d, Y').' is bookable again.');
    }
}
