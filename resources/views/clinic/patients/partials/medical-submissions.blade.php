{{-- Health declaration submission history.
     Expects: $submissions (ActivityLog collection, user eager-loaded). --}}
<div class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_45px_-28px_rgba(10,47,37,0.45)] ring-1 ring-clinic-950/5">
    <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b border-slate-200">
        <h3 class="font-semibold text-lg text-clinic-950">🗓️ Submission History</h3>
        <span class="text-xs text-slate-500">Health declaration filings</span>
    </div>

    <div class="px-6 py-5">
        @if ($submissions->isEmpty())
            <p class="text-sm text-slate-400">No submissions recorded yet — every save of the health declaration is logged here with its date.</p>
        @else
            <ol class="space-y-2">
                @foreach ($submissions as $submission)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
                        <span class="flex-1 text-slate-700">
                            📋 <span class="font-semibold">{{ $submission->description }}</span>
                            <span class="text-slate-500">— by {{ $submission->user?->name ?? 'Unknown user' }}</span>
                        </span>
                        <span class="text-xs font-medium text-clinic-700 whitespace-nowrap">
                            {{ $submission->created_at->format('M d, Y g:i A') }}
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
