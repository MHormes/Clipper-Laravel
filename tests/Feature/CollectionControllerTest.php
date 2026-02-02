<?php

use App\Models\Series;
use App\Models\User;
use App\Models\Clipper;
use App\Models\CollectedClipper;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
});

test('users can see their collection via redirect', function () {
    $series = Series::factory()->create(['accepted_by' => $this->admin->id]);
    $clipper = Clipper::factory()->create(['series_id' => $series->id, 'accepted_by' => $this->admin->id]);
    
    CollectedClipper::create([
        'user_id' => $this->user->id,
        'clipper_id' => $clipper->id
    ]);

    $this->actingAs($this->user);
    $response = $this->get(route('collection.index'));
    
    $response->assertStatus(301);
    $response->assertRedirect(route('series.index', ['filter' => 'collected']));
});

test('users can see their collection via filtered series catalog', function () {
    $series = Series::factory()->create(['accepted_by' => $this->admin->id]);
    $clipper = Clipper::factory()->create(['series_id' => $series->id, 'accepted_by' => $this->admin->id]);
    
    CollectedClipper::create([
        'user_id' => $this->user->id,
        'clipper_id' => $clipper->id
    ]);

    $this->actingAs($this->user);
    $response = $this->get(route('series.index', ['filter' => 'collected']));
    
    $response->assertStatus(200);
    $response->assertSee($series->name);
});

test('users can toggle a clipper in their collection', function () {
    $clipper = Clipper::factory()->create(['accepted_by' => $this->admin->id]);

    $this->actingAs($this->user);
    
    // Add to collection
    $this->post(route('clippers.toggle', $clipper));
    $this->assertDatabaseHas('collected_clippers', [
        'user_id' => $this->user->id,
        'clipper_id' => $clipper->id
    ]);

    // Remove from collection
    $this->post(route('clippers.toggle', $clipper));
    $this->assertDatabaseMissing('collected_clippers', [
        'user_id' => $this->user->id,
        'clipper_id' => $clipper->id
    ]);
});

test('users can toggle an entire series', function () {
    $series = Series::factory()->create(['accepted_by' => $this->admin->id]);
    Clipper::factory()->create(['series_id' => $series->id, 'accepted_by' => $this->admin->id]);
    Clipper::factory()->create(['series_id' => $series->id, 'accepted_by' => $this->admin->id]);

    $this->actingAs($this->user);
    
    // Add entire series
    $this->post(route('series.toggle-collection', $series));
    $this->assertEquals(2, $this->user->myCollection()->count());

    // Remove entire series
    $this->post(route('series.toggle-collection', $series));
    $this->assertEquals(0, $this->user->myCollection()->count());
});

test('users can update clipper notes in their collection', function () {
    $clipper = Clipper::factory()->create(['accepted_by' => $this->admin->id]);
    CollectedClipper::create([
        'user_id' => $this->user->id,
        'clipper_id' => $clipper->id
    ]);

    $this->actingAs($this->user);
    $response = $this->patch(route('collection.update', $clipper), [
        'notes' => 'Some cool notes',
        'location_bought' => 'Amsterdam'
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('collected_clippers', [
        'user_id' => $this->user->id,
        'clipper_id' => $clipper->id,
        'notes' => 'Some cool notes',
        'location_bought' => 'Amsterdam'
    ]);
});
