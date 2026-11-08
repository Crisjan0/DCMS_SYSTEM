@php
    $action = route('clinic.patients.medical-history.store', ['patient' => $patient]);
@endphp

@include('clinic.patients.partials.medical-form')
