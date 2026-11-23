{{-- Shows the one-time "status" message set with ->with('status', '...') on a redirect. --}}
@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
         class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
        <div class="flex items-start justify-between gap-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            <span>{{ session('status') }}</span>
            <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-800" aria-label="Dismiss">&times;</button>
        </div>
    </div>
@endif
@if ($errors->has('invitation') || $errors->has('member'))
    <div class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            {{ $errors->first('invitation') ?: $errors->first('member') }}
        </div>
    </div>
@endif
