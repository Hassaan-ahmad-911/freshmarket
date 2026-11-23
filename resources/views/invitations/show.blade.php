<x-guest-layout>
    <h1 class="text-lg font-semibold text-gray-900">Join {{ $invitation->supplier->name }}</h1>
    <p class="mt-1 text-sm text-gray-600">You've been invited to help manage this store on FreshMarket.</p>

    @error('invitation')
        <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @auth
        @if (strcasecmp(Auth::user()->email, $invitation->email) === 0)
            <form method="POST" action="{{ route('invitations.accept', $token) }}" class="mt-6">
                @csrf
                <x-primary-button class="w-full justify-center">Accept invitation</x-primary-button>
            </form>
        @else
            <p class="mt-6 text-sm text-gray-700">
                This invitation was sent to <strong>{{ $invitation->email }}</strong>, but you're logged in as {{ Auth::user()->email }}.
                Log out and open the link again.
            </p>
        @endif
    @else
        @if (\App\Models\User::where('email', $invitation->email)->exists())
            <p class="mt-6 text-sm text-gray-700">
                You already have an account. <a href="{{ route('login') }}" class="underline">Log in</a> as {{ $invitation->email }}, then open this link again.
            </p>
        @else
            <form method="POST" action="{{ route('invitations.accept', $token) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <x-input-label value="Email" />
                    <x-text-input type="email" class="block mt-1 w-full bg-gray-100" :value="$invitation->email" disabled />
                </div>
                <div>
                    <x-input-label for="name" value="Your name" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password" value="Choose a password" />
                    <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirm password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" required autocomplete="new-password" />
                </div>
                <x-primary-button class="w-full justify-center">Create account &amp; join</x-primary-button>
            </form>
        @endif
    @endauth
</x-guest-layout>
