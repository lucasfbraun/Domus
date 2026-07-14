<?php

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Receiver;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('receiver portal only sees own contracts and charges', function () {
    $receiverUser = User::factory()->receiver()->create();
    $receiver = Receiver::factory()->create(['user_id' => $receiverUser->id]);
    $otherReceiver = Receiver::factory()->create();

    $ownContract = Contract::factory()->active()->for($receiver)->create();
    Contract::factory()->active()->for($otherReceiver)->create();

    $ownCharge = Charge::factory()->open()->for($ownContract)->for($receiver)->create([
        'reference' => '2026-07',
        'original_amount' => 1500.5,
        'status' => ChargeStatus::Paid,
    ]);
    Charge::factory()->open()->for(
        Contract::factory()->active()->for($otherReceiver)->create()
    )->for($otherReceiver)->create();

    $response = $this->actingAs($receiverUser)
        ->get(route('receiver.portal'))
        ->assertSuccessful();

    $contracts = collect($response->viewData('page')['props']['contracts']['data']);
    $charges = collect($response->viewData('page')['props']['charges']['data']);

    expect($contracts)->toHaveCount(1)
        ->and($contracts->first()['id'])->toBe($ownContract->id)
        ->and($charges)->toHaveCount(1)
        ->and($charges->first()['id'])->toBe($ownCharge->id)
        ->and($charges->first()['description'])->toBe('2026-07')
        ->and($charges->first()['amount'])->toBe(1500.5)
        ->and($charges->first()['is_paid'])->toBeTrue()
        ->and($charges->first()['tenant'])->toBe($ownContract->tenant->name)
        ->and($charges->first()['property'])->toBe($ownContract->property->name);
});

test('receiver can download receipt for own paid charge', function () {
    $receiverUser = User::factory()->receiver()->create();
    $receiver = Receiver::factory()->create(['user_id' => $receiverUser->id]);
    $contract = Contract::factory()->active()->for($receiver)->create();
    $charge = Charge::factory()->for($contract)->for($receiver)->create([
        'status' => ChargeStatus::Paid,
    ]);

    $this->actingAs($receiverUser)
        ->get(route('charges.receipt', $charge))
        ->assertSuccessful()
        ->assertDownload('recibo-'.$charge->id.'.pdf');
});

test('receiver cannot download receipt for another receivers charge', function () {
    $receiverUser = User::factory()->receiver()->create();
    Receiver::factory()->create(['user_id' => $receiverUser->id]);

    $otherReceiver = Receiver::factory()->create();
    $charge = Charge::factory()->for(
        Contract::factory()->active()->for($otherReceiver)->create()
    )->for($otherReceiver)->create([
        'status' => ChargeStatus::Paid,
    ]);

    $this->actingAs($receiverUser)
        ->get(route('charges.receipt', $charge))
        ->assertForbidden();
});
