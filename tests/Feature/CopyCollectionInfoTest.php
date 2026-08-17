<?php

use App\Models\Clipper;
use App\Models\CollectedClipper;
use App\Models\Series;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user  = User::factory()->create(['role' => 'user']);

    $this->series = Series::factory()->create(['accepted_by' => $this->admin->id]);

    $this->source = Clipper::factory()->create([
        'series_id'   => $this->series->id,
        'accepted_by' => $this->admin->id,
    ]);

    $this->target = Clipper::factory()->create([
        'series_id'   => $this->series->id,
        'accepted_by' => $this->admin->id,
    ]);

    CollectedClipper::create([
        'user_id'         => $this->user->id,
        'clipper_id'      => $this->source->id,
        'notes'           => 'Flea market find',
        'location_bought' => '51.50, -0.12',
    ]);

    CollectedClipper::create([
        'user_id'    => $this->user->id,
        'clipper_id' => $this->target->id,
    ]);
});

test('collection list endpoint returns all collected clippers for the user', function () {
    $this->actingAs($this->user);

    $response = $this->getJson(route('collection.list'));

    $response->assertStatus(200);
    $response->assertJsonCount(2);
    $response->assertJsonFragment(['clipper_id' => $this->source->id]);
    $response->assertJsonFragment(['clipper_id' => $this->target->id]);
});

test('collection list endpoint excludes clippers not owned by the user', function () {
    $other = User::factory()->create(['role' => 'user']);
    $otherClipper = Clipper::factory()->create([
        'series_id'   => $this->series->id,
        'accepted_by' => $this->admin->id,
    ]);
    CollectedClipper::create(['user_id' => $other->id, 'clipper_id' => $otherClipper->id]);

    $this->actingAs($this->user);

    $response = $this->getJson(route('collection.list'));
    $response->assertStatus(200);

    $ids = collect($response->json())->pluck('clipper_id')->all();
    expect($ids)->not->toContain($otherClipper->id);
});

test('collection list endpoint 404s for a plain (non-JSON) browser request', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('collection.list'));

    $response->assertStatus(404);
});

test('user can copy notes to another collected clipper', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$this->target->id],
        'fields'      => ['notes'],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('collected_clippers', [
        'user_id'    => $this->user->id,
        'clipper_id' => $this->target->id,
        'notes'      => 'Flea market find',
    ]);
});

test('user can copy location to another collected clipper', function () {
    $this->actingAs($this->user);

    $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$this->target->id],
        'fields'      => ['location_bought'],
    ]);

    $this->assertDatabaseHas('collected_clippers', [
        'user_id'         => $this->user->id,
        'clipper_id'      => $this->target->id,
        'location_bought' => '51.50, -0.12',
    ]);
});

test('user can copy both fields at once', function () {
    $this->actingAs($this->user);

    $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$this->target->id],
        'fields'      => ['notes', 'location_bought'],
    ]);

    $this->assertDatabaseHas('collected_clippers', [
        'user_id'         => $this->user->id,
        'clipper_id'      => $this->target->id,
        'notes'           => 'Flea market find',
        'location_bought' => '51.50, -0.12',
    ]);
});

test('copying only selected fields does not overwrite other fields', function () {
    CollectedClipper::where('user_id', $this->user->id)
        ->where('clipper_id', $this->target->id)
        ->update(['notes' => 'My own note']);

    $this->actingAs($this->user);

    $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$this->target->id],
        'fields'      => ['location_bought'],
    ]);

    $this->assertDatabaseHas('collected_clippers', [
        'user_id'         => $this->user->id,
        'clipper_id'      => $this->target->id,
        'notes'           => 'My own note',
        'location_bought' => '51.50, -0.12',
    ]);
});

test('user cannot copy from a clipper they do not own', function () {
    $other = User::factory()->create(['role' => 'user']);

    $this->actingAs($other);

    $response = $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$this->target->id],
        'fields'      => ['notes'],
    ]);

    // Source is not in other user's collection → 404
    $response->assertStatus(404);
});

test('user cannot copy to clippers they do not own', function () {
    $other = User::factory()->create(['role' => 'user']);
    $otherTarget = Clipper::factory()->create([
        'series_id'   => $this->series->id,
        'accepted_by' => $this->admin->id,
    ]);
    CollectedClipper::create(['user_id' => $other->id, 'clipper_id' => $otherTarget->id, 'notes' => 'keep me']);

    $this->actingAs($this->user);

    $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$otherTarget->id],
        'fields'      => ['notes'],
    ]);

    // Should not have updated the other user's record
    $this->assertDatabaseHas('collected_clippers', [
        'user_id'    => $other->id,
        'clipper_id' => $otherTarget->id,
        'notes'      => 'keep me',
    ]);
});

test('copy-to requires at least one clipper id', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [],
        'fields'      => ['notes'],
    ]);

    $response->assertSessionHasErrors('clipper_ids');
});

test('copy-to requires at least one field', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$this->target->id],
        'fields'      => [],
    ]);

    $response->assertSessionHasErrors('fields');
});

test('copy-to rejects invalid field names', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$this->target->id],
        'fields'      => ['malicious_field'],
    ]);

    $response->assertSessionHasErrors('fields.0');
});

test('unauthenticated users cannot access copy endpoints', function () {
    $response = $this->post(route('collection.copy-to', $this->source->id), [
        'clipper_ids' => [$this->target->id],
        'fields'      => ['notes'],
    ]);

    $response->assertRedirect(route('login'));

    // JSON requests get 401 instead of redirect
    $this->getJson(route('collection.list'))->assertStatus(401);
});
