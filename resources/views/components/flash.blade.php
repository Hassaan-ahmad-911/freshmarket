{{-- One-time messages (->with('status', '...')) shown as toasts that slide in from the right. --}}
@php($error = $errors->first('invitation') ?: $errors->first('member') ?: $errors->first('refund') ?: $errors->first('order'))
@if (session('status') || $error)
    <div class="pointer-events-none fixed inset-x-4 top-20 z-50 flex flex-col items-end gap-3 sm:inset-x-auto sm:right-6" aria-live="polite">
        @if (session('status'))
            <div x-data="toast(5500)" x-show="open" x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 translate-x-8"
                 class="pointer-events-auto flex w-full max-w-sm animate-toast-in items-start gap-3 rounded-2xl border border-brand-200 bg-white/95 p-4 text-sm shadow-lift backdrop-blur">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-brand-700"><x-icon name="check-circle" /></span>
                <p class="flex-1 pt-1.5 text-stone-700">{{ session('status') }}</p>
                <button type="button" @click="open = false" class="rounded-lg p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-600" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
        @endif
        @if ($error)
            <div x-data="toast(9000)" x-show="open" x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 translate-x-8"
                 class="pointer-events-auto flex w-full max-w-sm animate-toast-in items-start gap-3 rounded-2xl border border-red-200 bg-white/95 p-4 text-sm shadow-lift backdrop-blur">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600"><x-icon name="warning" /></span>
                <p class="flex-1 pt-1.5 text-stone-700">{{ $error }}</p>
                <button type="button" @click="open = false" class="rounded-lg p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-600" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
        @endif
    </div>
@endif
