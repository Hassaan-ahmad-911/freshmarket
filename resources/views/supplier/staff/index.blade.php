<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="POST" action="{{ route('supplier.staff.invite') }}" class="bg-white shadow-sm sm:rounded-lg p-6">
                @csrf
                <h3 class="font-semibold">Invite a team member</h3>
                <p class="mt-1 text-sm text-gray-600">Staff can manage products and orders. Only you (the owner) can change the store profile, see earnings and manage staff.</p>
                <div class="mt-4 flex flex-col sm:flex-row gap-3">
                    <x-text-input name="email" type="email" placeholder="colleague@example.com" class="flex-1" :value="old('email')" required />
                    <x-primary-button>Send invitation</x-primary-button>
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <h3 class="px-6 py-4 border-b border-gray-100 font-semibold">Team</h3>
                <ul class="divide-y divide-gray-100">
                    @foreach ($members as $member)
                        <li class="px-6 py-3 flex items-center justify-between text-sm">
                            <div>
                                <p class="font-medium">{{ $member->name }}</p>
                                <p class="text-gray-500">{{ $member->email }}</p>
                            </div>
                            @if ($supplier->isOwnedBy($member))
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">Owner</span>
                            @else
                                <form method="POST" action="{{ route('supplier.staff.destroy', $member) }}"
                                      x-data @submit.prevent="if (confirm('Remove this person from your store?')) $el.submit()">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Remove</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($invitations->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <h3 class="px-6 py-4 border-b border-gray-100 font-semibold">Pending invitations</h3>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($invitations as $invitation)
                            <li class="px-6 py-3 flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-medium">{{ $invitation->email }}</p>
                                    <p class="text-gray-500">Expires {{ $invitation->expires_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('supplier.staff.invitations.destroy', $invitation) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-600 hover:underline">Cancel</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
