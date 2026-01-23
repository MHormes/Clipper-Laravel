<?php

use App\Models\User;
use App\Models\Series;
use App\Models\Clipper;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
    
    // Ensure System User exists for deletion tests
    $this->systemUserId = '019bb7be-fec4-7390-a7e1-63b1a0c1067f';
    if (!User::where('id', $this->systemUserId)->exists()) {
        User::factory()->create([
            'id' => $this->systemUserId,
            'name' => 'System',
            'email' => 'system@clipper.com',
            'role' => 'user'
        ]);
    }
});

test('admins can view user list', function () {
    $this->actingAs($this->admin);
    $response = $this->get(route('admin.users.index'));
    
    $response->assertStatus(200);
    $response->assertSee($this->user->name);
});

test('non-admins cannot view user list', function () {
    $this->actingAs($this->user);
    $response = $this->get(route('admin.users.index'));
    
    $response->assertStatus(403);
});

test('admins can update user roles', function () {
    $this->actingAs($this->admin);
    
    $response = $this->put(route('admin.users.update', $this->user), [
        'role' => 'admin',
        'is_active' => true
    ]);

    $response->assertRedirect();
    $this->assertEquals('admin', $this->user->fresh()->role);
});

test('admins cannot remove their own admin role', function () {
    $this->actingAs($this->admin);
    
    $response = $this->put(route('admin.users.update', $this->admin), [
        'role' => 'user',
        'is_active' => true
    ]);

    $response->assertSessionHas('error');
    $this->assertEquals('admin', $this->admin->fresh()->role);
});

test('admins can delete users and Re-assign Series and Clippers to the System User', function () {
    $userToDelete = User::factory()->create();
    $series = Series::factory()->create(['requested_by' => $userToDelete->id]);
    $clipper = Clipper::factory()->create(['requested_by' => $userToDelete->id]);

    $this->actingAs($this->admin);
    
    $response = $this->delete(route('admin.users.destroy', $userToDelete));

    $response->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    
    // Check Re-assignment
    $this->assertEquals($this->systemUserId, $series->fresh()->requested_by);
    $this->assertEquals($this->systemUserId, $clipper->fresh()->requested_by);
});

test('admins cannot delete themselves', function () {
    $this->actingAs($this->admin);
    
    $response = $this->delete(route('admin.users.destroy', $this->admin));

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});
