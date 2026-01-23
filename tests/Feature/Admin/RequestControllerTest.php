<?php

use App\Models\User;
use App\Models\Series;
use App\Models\Clipper;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
});

test('admins can view pending series requests', function () {
    $pendingSeries = Series::factory()->create(['accepted_by' => null]);

    $this->actingAs($this->admin);
    $response = $this->get(route('admin.requests.series.index'));
    
    $response->assertStatus(200);
    $response->assertSee($pendingSeries->name);
});

test('admins can accept series request fully', function () {
    $pendingSeries = Series::factory()->create(['accepted_by' => null]);
    $clipper = Clipper::factory()->create(['series_id' => $pendingSeries->id, 'accepted_by' => null]);

    $this->actingAs($this->admin);
    
    $response = $this->post(route('admin.requests.series.accept', $pendingSeries), [
        'mode' => 'full'
    ]);

    $response->assertRedirect(route('admin.requests.series.index'));
    $this->assertNotNull($pendingSeries->fresh()->accepted_by);
    $this->assertNotNull($clipper->fresh()->accepted_by);
});

test('admins can decline series request', function () {
    $pendingSeries = Series::factory()->create(['accepted_by' => null]);

    $this->actingAs($this->admin);
    
    $response = $this->delete(route('admin.requests.series.decline', $pendingSeries));

    $response->assertRedirect(route('admin.requests.series.index'));
    $this->assertDatabaseMissing('series', ['id' => $pendingSeries->id]);
});

test('admins can accept individual clipper request', function () {
    $series = Series::factory()->create(['accepted_by' => $this->admin->id]);
    $pendingClipper = Clipper::factory()->create(['series_id' => $series->id, 'accepted_by' => null]);

    $this->actingAs($this->admin);
    
    $response = $this->post(route('admin.requests.clippers.accept', $pendingClipper));

    $response->assertRedirect();
    $this->assertNotNull($pendingClipper->fresh()->accepted_by);
});

test('admins can decline individual clipper request', function () {
    $series = Series::factory()->create(['accepted_by' => $this->admin->id]);
    $pendingClipper = Clipper::factory()->create(['series_id' => $series->id, 'accepted_by' => null]);

    $this->actingAs($this->admin);
    
    $response = $this->delete(route('admin.requests.clippers.decline', $pendingClipper));

    $response->assertRedirect();
    $this->assertDatabaseMissing('clippers', ['id' => $pendingClipper->id]);
});
