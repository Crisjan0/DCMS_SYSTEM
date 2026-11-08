<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyInvoiceController extends Controller
{
    /**
     * The logged-in patient's own invoices.
     */
    public function index(Request $request): View
    {
        $patient = $request->user()->patient;

        $invoices = $patient
            ? $patient->invoices()->latest('id')->paginate(10)
            : null;

        $outstanding = $patient
            ? (float) $patient->invoices()
                ->whereIn('status', ['unpaid', 'partial'])
                ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as due')
                ->value('due')
            : 0.0;

        return view('patient.my-invoices', [
            'invoices' => $invoices,
            'outstanding' => $outstanding,
        ]);
    }
}
