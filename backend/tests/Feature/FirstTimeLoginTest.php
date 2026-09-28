<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('first time login does not issue token and requires password change', function () {
    $user = User::create([
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'email' => 'alice@test.edu',
        'password' => Hash::make('TemporaryPassword123!'),
        'is_first_login' => true,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'alice@test.edu',
        'password' => 'TemporaryPassword123!',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'requires_password_change' => true,
            'email' => 'alice@test.edu',
        ],
    ]);

    // Ensure NO token was returned!
    expect($response->json('data.token'))->toBeNull();

    // Now change password via first-time-password endpoint
    $changeResponse = $this->postJson('/api/auth/first-time-password', [
        'email' => 'alice@test.edu',
        'current_password' => 'TemporaryPassword123!',
        'new_password' => 'NewSecurePassword456!',
        'new_password_confirmation' => 'NewSecurePassword456!',
    ]);

    $changeResponse->assertStatus(200);
    $changeResponse->assertJson(['success' => true]);

    $user->refresh();
    expect($user->is_first_login)->toBeFalse();

    // Now user can log in with new password and receives token
    $newLoginResponse = $this->postJson('/api/auth/login', [
        'email' => 'alice@test.edu',
        'password' => 'NewSecurePassword456!',
    ]);

    $newLoginResponse->assertStatus(200);
    expect($newLoginResponse->json('data.token'))->not->toBeNull();
    expect($newLoginResponse->json('data.user.is_first_login'))->toBeFalse();
});
