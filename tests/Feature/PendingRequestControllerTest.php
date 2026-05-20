<?php

use App\Models\User;
use App\Models\Series;
use App\Models\Clipper;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    $this->otherUser = User::factory()->create(['role' => 'user']);
});

test('users can view their own pending series requests', function () {
    $mySeries = Series::factory()->create([
        'requested_by' => $this->user->id,
        'accepted_by' => null,
        'name' => 'My Pending Series'
    ]);
    
    $otherSeries = Series::factory()->create([
        'requested_by' => $this->otherUser->id,
        'accepted_by' => null,
        'name' => 'Other User Pending Series'
    ]);

    $this->actingAs($this->user);
    $response = $this->get(route('pending-requests.series'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('requests/PendingSeriesIndex')
        ->has('series', 1)
        ->where('series.0.name', 'My Pending Series')
    );
});

test('users can view their own pending clipper requests for accepted series', function () {
    $acceptedSeries = Series::factory()->create(['accepted_by' => $this->user->id]);
    
    $myClipper = Clipper::factory()->create([
        'series_id' => $acceptedSeries->id,
        'requested_by' => $this->user->id,
        'accepted_by' => null,
    ]);
    
    $otherClipper = Clipper::factory()->create([
        'series_id' => $acceptedSeries->id,
        'requested_by' => $this->otherUser->id,
        'accepted_by' => null,
    ]);

    $this->actingAs($this->user);
    $response = $this->get(route('pending-requests.clippers'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('requests/PendingClippersIndex')
        ->has('groupedClippers', 1) // One series group
    );
});

test('clippers of pending series do not show up in clipper requests list', function () {
    $pendingSeries = Series::factory()->create([
        'requested_by' => $this->user->id,
        'accepted_by' => null,
    ]);
    
    $clipperOfPendingSeries = Clipper::factory()->create([
        'series_id' => $pendingSeries->id,
        'requested_by' => $this->user->id,
        'accepted_by' => null,
    ]);

    $this->actingAs($this->user);
    $response = $this->get(route('pending-requests.clippers'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('requests/PendingClippersIndex')
        ->has('groupedClippers', 0)
    );
});
