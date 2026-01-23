<?php

namespace Tests\Feature;

use App\Models\Clipper;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /**
     * Test case-insensitive search in Series Catalog.
     */
    public function test_series_catalog_search_is_case_insensitive()
    {
        Series::factory()->create(['name' => 'Lava Lamp', 'accepted_by' => $this->admin->id]);
        Series::factory()->create(['name' => 'Blue Ocean', 'accepted_by' => $this->admin->id]);

        $this->actingAs($this->user);

        // Search with exact case
        $response = $this->get(route('series.index', ['search' => 'Lava']));
        $response->assertStatus(200);
        $response->assertSee('Lava Lamp');
        $response->assertDontSee('Blue Ocean');

        // Search with lowercase
        $response = $this->get(route('series.index', ['search' => 'lava']));
        $response->assertStatus(200);
        $response->assertSee('Lava Lamp');

        // Search with uppercase
        $response = $this->get(route('series.index', ['search' => 'LAVA']));
        $response->assertStatus(200);
        $response->assertSee('Lava Lamp');
    }

    /**
     * Test case-insensitive search in My Collection.
     */
    public function test_collection_search_is_case_insensitive()
    {
        $series1 = Series::factory()->create(['name' => 'Vintage Cars', 'accepted_by' => $this->admin->id]);
        $series2 = Series::factory()->create(['name' => 'Modern Art', 'accepted_by' => $this->admin->id]);

        $clipper1 = Clipper::factory()->create(['series_id' => $series1->id, 'accepted_by' => $this->admin->id]);
        $clipper2 = Clipper::factory()->create(['series_id' => $series2->id, 'accepted_by' => $this->admin->id]);

        $this->actingAs($this->user);

        // Add to collection
        $this->user->myCollection()->create(['clipper_id' => $clipper1->id]);
        $this->user->myCollection()->create(['clipper_id' => $clipper2->id]);

        // Search with exact case
        $response = $this->get(route('collection.index', ['search' => 'Vintage']));
        $response->assertStatus(200);
        $response->assertSee('Vintage Cars');
        $response->assertDontSee('Modern Art');

        // Search with lowercase
        $response = $this->get(route('collection.index', ['search' => 'vintage']));
        $response->assertStatus(200);
        $response->assertSee('Vintage Cars');

        // Search with uppercase
        $response = $this->get(route('collection.index', ['search' => 'VINTAGE']));
        $response->assertStatus(200);
        $response->assertSee('Vintage Cars');
    }

    /**
     * Test case-insensitive search in Clipper List (Board View).
     */
    public function test_clipper_list_search_is_case_insensitive()
    {
        $series1 = Series::factory()->create(['name' => 'Space Nebula', 'accepted_by' => $this->admin->id]);
        $series2 = Series::factory()->create(['name' => 'Earth Nature', 'accepted_by' => $this->admin->id]);

        $clipper1 = Clipper::factory()->create(['series_id' => $series1->id, 'accepted_by' => $this->admin->id]);
        $clipper2 = Clipper::factory()->create(['series_id' => $series2->id, 'accepted_by' => $this->admin->id]);

        $this->actingAs($this->user);

        // Add to collection
        $this->user->myCollection()->create(['clipper_id' => $clipper1->id]);
        $this->user->myCollection()->create(['clipper_id' => $clipper2->id]);

        // Search with exact case
        $response = $this->get(route('collection.clippers', ['search' => 'Space']));
        $response->assertStatus(200);
        // Since the board view doesn't show series names directly in the grid items yet (except via links), 
        // we can check if the correct number of items is returned or if the series ID link is present.
        $response->assertSee($clipper1->image_data);
        $response->assertDontSee($clipper2->image_data);

        // Search with lowercase
        $response = $this->get(route('collection.clippers', ['search' => 'space']));
        $response->assertStatus(200);
        $response->assertSee($clipper1->image_data);

        // Search with uppercase
        $response = $this->get(route('collection.clippers', ['search' => 'SPACE']));
        $response->assertStatus(200);
        $response->assertSee($clipper1->image_data);
    }
}
