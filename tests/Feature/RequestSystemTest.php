<?php

namespace Tests\Feature;

use App\Models\Clipper;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
        Storage::fake('public');
    }

    public function test_user_can_submit_series_request()
    {
        $response = $this->actingAs($this->user)
            ->post(route('series.store'), [
                'name' => 'New Request Series',
                'custom' => false,
                'auto_add_to_collection' => true,
                'image' => UploadedFile::fake()->image('series.jpg'),
                'clippers' => [
                    ['image' => UploadedFile::fake()->image('clipper1.jpg')],
                    ['image' => UploadedFile::fake()->image('clipper2.jpg')],
                ]
            ]);

        $series = Series::where('name', 'New Request Series')->first();
        
        $response->assertRedirect(route('series.index'));
        $this->assertNotNull($series);
        $this->assertNull($series->accepted_by);
        $this->assertEquals($this->user->id, $series->requested_by);
        $this->assertCount(2, $series->clippers);
        $this->assertNull($series->clippers->first()->accepted_by);
        $this->assertTrue($series->clippers->first()->auto_add_to_collection);
    }

    public function test_admin_can_accept_series_fully()
    {
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
        $this->assertNotNull($series->refresh()->accepted_by);
        $this->assertEquals($this->admin->id, $series->accepted_by);
        $this->assertNotNull($clippers->first()->refresh()->accepted_by);
        $this->assertDatabaseHas('collected_clippers', [
            'user_id' => $this->user->id,
            'clipper_id' => $clippers->first()->id,
        ]);
        $this->assertDatabaseHas('collected_clippers', [
            'user_id' => $this->user->id,
            'clipper_id' => $clippers->last()->id,
        ]);
    }

    public function test_admin_can_accept_series_partially()
    {
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
        $this->assertNotNull($series->refresh()->accepted_by);
        
        // Clipper 1 should be accepted
        $this->assertNotNull($clipper1->refresh()->accepted_by);
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
    }

    public function test_admin_can_decline_series_request()
    {
        $series = Series::factory()->create(['requested_by' => $this->user->id, 'accepted_by' => null]);
        $clipper = Clipper::factory()->create(['series_id' => $series->id, 'accepted_by' => null]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.requests.series.decline', $series->id));

        $response->assertRedirect(route('admin.requests.series.index'));
        $this->assertDatabaseMissing('series', ['id' => $series->id]);
        $this->assertDatabaseMissing('clippers', ['id' => $clipper->id]);
    }

    public function test_user_can_request_clippers_for_existing_series()
    {
        $series = Series::factory()->create(['accepted_by' => $this->admin->id]);

        $response = $this->actingAs($this->user)
            ->post(route('series.store-clipper-request', $series->id), [
                'auto_add_to_collection' => true,
                'clippers' => [
                    ['image' => UploadedFile::fake()->image('req_clipper.jpg')]
                ]
            ]);

        $response->assertRedirect(route('series.show', ['series' => $series->id, 'slug' => $series->slug]));
        $this->assertDatabaseHas('clippers', [
            'series_id' => $series->id,
            'requested_by' => $this->user->id,
            'auto_add_to_collection' => true,
            'accepted_by' => null
        ]);
    }

    public function test_only_accepted_series_are_visible_in_catalog()
    {
        Series::factory()->create(['name' => 'Accepted Series', 'accepted_by' => $this->admin->id]);
        Series::factory()->create(['name' => 'Pending Request', 'accepted_by' => null]);

        $response = $this->actingAs($this->user)->get(route('series.index'));

        $response->assertStatus(200);
        $response->assertSee('Accepted Series');
        $response->assertDontSee('Pending Request');
    }
}
