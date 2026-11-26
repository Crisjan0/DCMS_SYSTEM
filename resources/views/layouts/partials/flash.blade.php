@if (session('success'))
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            ✅ {{ session('success') }}
        </div>
    </div>
@endif

@if (session('error'))
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <div class="p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            {{ session('error') }}
        </div>
    </div>
@endif
