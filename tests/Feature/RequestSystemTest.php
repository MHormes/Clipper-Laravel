<?php

use App\Models\Clipper;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
    Storage::fake('public');
});

test('user can submit series request', function () {
    $response = $this->actingAs($this->user)
        ->post(route('series.store'), [
            'name' => 'New Request Series',
            'custom' => false,
            'image' => UploadedFile::fake()->image('series.jpg'),
            'clippers' => [
                ['image' => UploadedFile::fake()->image('clipper1.jpg'), 'auto_add_to_collection' => true],
                ['image' => UploadedFile::fake()->image('clipper2.jpg'), 'auto_add_to_collection' => false],
            ]
        ]);

    $series = Series::where('name', 'New Request Series')->first();
    
    $response->assertRedirect(route('series.index'));
    expect($series)->not->toBeNull();
    expect($series->accepted_by)->toBeNull();
    expect($series->requested_by)->toBe($this->user->id);
    expect($series->clippers)->toHaveCount(2);
    expect($series->clippers[0]->accepted_by)->toBeNull();
    expect($series->clippers[0]->auto_add_to_collection)->toBeTrue();
    expect($series->clippers[1]->auto_add_to_collection)->toBeFalse();
});

test('admin can accept series fully', function () {
    $series = Series::factory()->create(['requested_by' => $this->user->id, 'accepted_by' => null]);
    $clippers = Clipper::factory()->count(2)->create([
        'series_id' => $series->id,
        'requested_by' => $this->user->id,
        'auto_add_to_collection' => true,
        'accepted_by' => null,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.requests.series.accept', $series->id), [
            'mode' => 'full'
        ]);

    $response->assertRedirect(route('admin.requests.series.index'));
    expect($series->refresh()->accepted_by)->not->toBeNull();
    expect($series->accepted_by)->toBe($this->admin->id);
    expect($clippers->first()->refresh()->accepted_by)->not->toBeNull();
    $this->assertDatabaseHas('collected_clippers', [
        'user_id' => $this->user->id,
        'clipper_id' => $clippers->first()->id,
    ]);
    $this->assertDatabaseHas('collected_clippers', [
        'user_id' => $this->user->id,
        'clipper_id' => $clippers->last()->id,
    ]);
});

test('admin can accept series partially', function () {
    $series = Series::factory()->create(['requested_by' => $this->user->id, 'accepted_by' => null]);
    $clipper1 = Clipper::factory()->create([
        'series_id' => $series->id,
        'requested_by' => $this->user->id,
        'auto_add_to_collection' => true,
        'accepted_by' => null,
    ]);
    $clipper2 = Clipper::factory()->create([
        'series_id' => $series->id,
        'requested_by' => $this->user->id,
        'auto_add_to_collection' => true,
        'accepted_by' => null,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.requests.series.accept', $series->id), [
            'mode' => 'partial',
            'clipper_ids' => [$clipper1->id]
        ]);

    $response->assertRedirect(route('admin.requests.series.index'));
    expect($series->refresh()->accepted_by)->not->toBeNull();
    
    // Clipper 1 should be accepted
    expect($clipper1->refresh()->accepted_by)->not->toBeNull();
    $this->assertDatabaseHas('collected_clippers', [
        'user_id' => $this->user->id,
        'clipper_id' => $clipper1->id,
    ]);
    
    // Clipper 2 should be deleted
    $this->assertDatabaseMissing('clippers', ['id' => $clipper2->id]);
    $this->assertDatabaseMissing('collected_clippers', [
        'user_id' => $this->user->id,
        'clipper_id' => $clipper2->id,
    ]);
});

test('admin can decline series request', function () {
    $series = Series::factory()->create(['requested_by' => $this->user->id, 'accepted_by' => null]);
    $clipper = Clipper::factory()->create(['series_id' => $series->id, 'accepted_by' => null]);

    $response = $this->actingAs($this->admin)
        ->delete(route('admin.requests.series.decline', $series->id));

    $response->assertRedirect(route('admin.requests.series.index'));
    $this->assertDatabaseMissing('series', ['id' => $series->id]);
    $this->assertDatabaseMissing('clippers', ['id' => $clipper->id]);
});

test('user can request clippers for existing series', function () {
    $series = Series::factory()->create(['accepted_by' => $this->admin->id]);

    $response = $this->actingAs($this->user)
        ->post(route('series.store-clipper-request', $series->id), [
            'clippers' => [
                [
                    'image' => UploadedFile::fake()->image('req_clipper.jpg'),
                    'auto_add_to_collection' => true,
                ]
            ]
        ]);

    $response->assertRedirect(route('series.show', ['series' => $series->id, 'slug' => $series->slug]));
    $this->assertDatabaseHas('clippers', [
        'series_id' => $series->id,
        'requested_by' => $this->user->id,
        'auto_add_to_collection' => true,
        'accepted_by' => null
    ]);
});

test('only accepted series are visible in catalog', function () {
    Series::factory()->create(['name' => 'Accepted Series', 'accepted_by' => $this->admin->id]);
    Series::factory()->create(['name' => 'Pending Request', 'accepted_by' => null]);

    $response = $this->actingAs($this->user)->get(route('series.index'));

    $response->assertStatus(200);
    $response->assertSee('Accepted Series');
    $response->assertDontSee('Pending Request');
});
