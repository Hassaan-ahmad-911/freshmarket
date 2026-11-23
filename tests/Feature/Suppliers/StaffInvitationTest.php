<?php

use App\Domain\Shared\Enums\Permission;
use App\Domain\Suppliers\Models\Supplier;
use App\Domain\Suppliers\Models\SupplierInvitation;
use App\Domain\Suppliers\Notifications\StaffInvitationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->supplier = Supplier::factory()->create();
    $this->owner = supplierOwner($this->supplier);
});

/** Invite someone and return the secret token that was emailed to them. */
function inviteAndCaptureToken(User $owner, string $email): string
{
    test()->actingAs($owner)->post(route('supplier.staff.invite'), ['email' => $email])->assertSessionHasNoErrors();

    $token = null;
    Notification::assertSentOnDemand(StaffInvitationNotification::class, function ($notification, $channels, $notifiable) use ($email, &$token) {
        $token = $notification->plainToken;

        return $notifiable->routes['mail'] === $email;
    });

    return $token;
}

it('emails an invitation and stores only a hash of the token', function () {
    $token = inviteAndCaptureToken($this->owner, 'new.staff@example.com');

    $invitation = SupplierInvitation::sole();
    expect($invitation->token_hash)->toBe(hash('sha256', $token))
        ->and($invitation->token_hash)->not->toBe($token)
        ->and($invitation->supplier_id)->toBe($this->supplier->id);
});

it('lets a new person accept by creating an account', function () {
    $token = inviteAndCaptureToken($this->owner, 'new.staff@example.com');
    auth()->logout();

    $this->post(route('invitations.accept', $token), [
        'name' => 'New Staff',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('supplier.dashboard'));

    $staff = User::where('email', 'new.staff@example.com')->firstOrFail();

    expect($staff->supplier_id)->toBe($this->supplier->id)
        ->and($staff->can(Permission::ManageProducts->value))->toBeTrue()
        ->and($staff->can(Permission::ManageStaff->value))->toBeFalse()
        ->and(SupplierInvitation::sole()->accepted_at)->not->toBeNull();

    $this->assertAuthenticatedAs($staff);
});

it('lets an existing customer accept while logged in', function () {
    $customer = User::factory()->customer()->create(['email' => 'shopper@example.com']);
    $token = inviteAndCaptureToken($this->owner, 'shopper@example.com');

    $this->actingAs($customer)->post(route('invitations.accept', $token))->assertRedirect(route('supplier.dashboard'));

    expect($customer->refresh()->supplier_id)->toBe($this->supplier->id)
        ->and($customer->isSupplierUser())->toBeTrue();
});

it('refuses an invitation accepted by someone with a different email', function () {
    $token = inviteAndCaptureToken($this->owner, 'intended@example.com');
    $intruder = User::factory()->customer()->create();

    $this->actingAs($intruder)->post(route('invitations.accept', $token))->assertSessionHasErrors('invitation');

    expect($intruder->refresh()->supplier_id)->toBeNull();
});

it('rejects expired, used and made-up tokens', function () {
    $token = inviteAndCaptureToken($this->owner, 'late@example.com');
    auth()->logout();

    $this->travel(SupplierInvitation::VALID_FOR_DAYS + 1)->days();

    $this->get(route('invitations.show', $token))->assertNotFound();
    $this->get(route('invitations.show', 'not-a-real-token'))->assertNotFound();
});

it('does not let staff invite or remove people', function () {
    $staff = supplierStaff($this->supplier);
    $colleague = supplierStaff($this->supplier);

    $this->actingAs($staff)->post(route('supplier.staff.invite'), ['email' => 'x@example.com'])->assertForbidden();
    $this->actingAs($staff)->delete(route('supplier.staff.destroy', $colleague))->assertForbidden();

    expect($colleague->refresh()->supplier_id)->toBe($this->supplier->id);
});

it('lets the owner remove a staff member, who becomes a customer', function () {
    $staff = supplierStaff($this->supplier);

    $this->actingAs($this->owner)->delete(route('supplier.staff.destroy', $staff))->assertRedirect();

    $staff->refresh();
    expect($staff->supplier_id)->toBeNull()
        ->and($staff->isSupplierUser())->toBeFalse()
        ->and($staff->isCustomer())->toBeTrue();
});

it('does not let an owner remove staff of another store', function () {
    $otherStaff = supplierStaff(Supplier::factory()->create());

    $this->actingAs($this->owner)->delete(route('supplier.staff.destroy', $otherStaff))->assertSessionHasErrors('member');

    expect($otherStaff->refresh()->supplier_id)->not->toBeNull();
});

it('will not invite someone who already works for a store', function () {
    $otherStaff = supplierStaff(Supplier::factory()->create());

    $this->actingAs($this->owner)
        ->post(route('supplier.staff.invite'), ['email' => $otherStaff->email])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});
