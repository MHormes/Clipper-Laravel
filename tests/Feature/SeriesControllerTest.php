<?php

use App\Models\Series;
use App\Models\User;
use App\Models\Clipper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
});

test('guests cannot visit series index', function () {
    $response = $this->get(route('series.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit series index', function () {
    $this->actingAs($this->user);
    $response = $this->get(route('series.index'));
    $response->assertStatus(200);
});

test('users can see accepted series but not pending ones', function () {
    $acceptedSeries = Series::factory()->create(['name' => 'Accepted Series', 'accepted_by' => $this->admin->id]);
    $pendingSeries = Series::factory()->create(['name' => 'Pending Series', 'accepted_by' => null]);

    $this->actingAs($this->user);
    $response = $this->get(route('series.index'));
    
    $response->assertSee('Accepted Series');
    $response->assertDontSee('Pending Series');
});

test('admins do not see pending series in the main catalog', function () {
    Series::factory()->create(['name' => 'Pending Series', 'accepted_by' => null]);

    $this->actingAs($this->admin);
    $response = $this->get(route('series.index'));
    
    $response->assertDontSee('Pending Series');
});

test('users cannot view pending series details', function () {
    $pendingSeries = Series::factory()->create(['accepted_by' => null]);

    $this->actingAs($this->user);
    $response = $this->get(route('series.show', $pendingSeries));
    
    $response->assertStatus(404);
});

test('admins can view pending series details', function () {
    $pendingSeries = Series::factory()->create(['accepted_by' => null]);

    $this->actingAs($this->admin);
    $response = $this->get(route('series.show', ['series' => $pendingSeries->id, 'slug' => $pendingSeries->slug]));
    
    $response->assertStatus(200);
});

test('users can submit a series request', function () {
    Storage::fake('public');

    $this->actingAs($this->user);
    
    $response = $this->post(route('series.store'), [
        'name' => 'New Request Series',
        'custom' => false,
        'image' => UploadedFile::fake()->image('series.jpg'),
        'clippers' => [
            ['image' => UploadedFile::fake()->image('clipper1.jpg'), 'series_number' => 1]
        ]
    ]);

    $response->assertRedirect(route('series.index'));
    $this->assertDatabaseHas('series', [
        'name' => 'New Request Series',
        'accepted_by' => null,
        'requested_by' => $this->user->id
    ]);
});

test('admins can create series directly', function () {
    Storage::fake('public');

    $this->actingAs($this->admin);
    
    $response = $this->post(route('series.store'), [
        'name' => 'Admin Series',
        'custom' => false,
        'image' => UploadedFile::fake()->image('series.jpg'),
        'clippers' => [
            ['image' => UploadedFile::fake()->image('clipper1.jpg'), 'series_number' => 1]
        ]
    ]);

    $this->assertDatabaseHas('series', [
        'name' => 'Admin Series',
        'accepted_by' => $this->admin->id
    ]);
    
    $series = Series::where('name', 'Admin Series')->first();
    $response->assertRedirect(route('series.show', ['series' => $series->id, 'slug' => $series->slug]));
});

test('non-admins cannot delete series', function () {
    $series = Series::factory()->create();

    $this->actingAs($this->user);
    $response = $this->delete(route('series.destroy', $series));
    
    $response->assertStatus(403); // Assuming middleware protection
});

test('admins can delete series', function () {
    $series = Series::factory()->create();

    $this->actingAs($this->admin);
    $response = $this->delete(route('series.destroy', $series));
    
    $response->assertRedirect(route('series.index'));
    $this->assertDatabaseMissing('series', ['id' => $series->id]);
});
