<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('users can view their profile settings', function () {
    $this->actingAs($this->user);
    $response = $this->get(route('profile.edit'));
    
    $response->assertStatus(200);
});

test('users can update their profile information', function () {
    $this->actingAs($this->user);
    
    $response = $this->patch(route('profile.update'), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirect(route('profile.edit'));
    $this->user->refresh();

    $this->assertEquals('Updated Name', $this->user->name);
    $this->assertEquals('updated@example.com', $this->user->email);
});

test('users can delete their account', function () {
    $this->actingAs($this->user);
    
    $response = $this->delete(route('profile.destroy'), [
        'password' => 'password', // Default factory password
    ]);

    $response->assertRedirect('/');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
});

test('password must be correct to delete account', function () {
    $this->actingAs($this->user);
    
    $response = $this->delete(route('profile.destroy'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseHas('users', ['id' => $this->user->id]);
});
