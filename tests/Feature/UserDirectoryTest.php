<?php

namespace Tests\Feature;

use App\Models\Clipper;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_user_directory(): void
    {
        $this->get(route('users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_directory_searches_by_name_only(): void
    {
        $viewer = User::factory()->create();
        $matchingByName = User::factory()->create([
            'name' => 'Alice Collector',
            'email' => 'alice@example.test',
        ]);

        $matchingByEmailOnly = User::factory()->create([
            'name' => 'Bob Archive',
            'email' => 'alice-hidden@example.test',
        ]);

        $this->actingAs($viewer)
            ->get(route('users.index', ['search' => 'alice']))
            ->assertOk()
            ->assertSee($matchingByName->name)
            ->assertDontSee($matchingByEmailOnly->name);
    }

    public function test_user_directory_includes_collection_and_contribution_stats(): void
    {
        $viewer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['name' => 'Stat User']);

        $officialSeries = Series::factory()->create([
            'custom' => false,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        $officialClippers = Clipper::factory()->count(4)->create([
            'series_id' => $officialSeries->id,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        foreach ($officialClippers as $clipper) {
            $target->myCollection()->create(['clipper_id' => $clipper->id]);
        }

        $customSeries = Series::factory()->create([
            'custom' => true,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        $customClippers = Clipper::factory()->count(2)->create([
            'series_id' => $customSeries->id,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        foreach ($customClippers as $clipper) {
            $target->myCollection()->create(['clipper_id' => $clipper->id]);
        }

        Series::factory()->create([
            'custom' => false,
            'requested_by' => $target->id,
            'accepted_by' => $admin->id,
        ]);

        Clipper::factory()->count(2)->create([
            'requested_by' => $target->id,
            'accepted_by' => $admin->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('users.index', ['search' => 'stat user']))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('users/Index')
                ->where('users.data.0.name', 'Stat User')
                ->where('users.data.0.collected_clippers_count', 6)
                ->where('users.data.0.completed_series_count', 2)
                ->where('users.data.0.contributions_count', 3)
            );
    }

    public function test_following_page_requires_auth_and_renders(): void
    {
        $this->get(route('users.following'))
            ->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('users.following'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->component('users/Following'));
    }

    public function test_user_profile_page_requires_auth_and_renders_collected_series(): void
    {
        $this->get(route('users.show', User::factory()->create()))
            ->assertRedirect(route('login'));

        $viewer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['name' => 'Target User']);

        $collectedSeries = Series::factory()->create([
            'name' => 'Target Collected',
            'custom' => false,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        $notCollectedSeries = Series::factory()->create([
            'name' => 'Not Collected',
            'custom' => false,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        $collectedClipper = Clipper::factory()->create([
            'series_id' => $collectedSeries->id,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        Clipper::factory()->create([
            'series_id' => $notCollectedSeries->id,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        $target->myCollection()->create(['clipper_id' => $collectedClipper->id]);

        $this->actingAs($viewer)
            ->get(route('users.show', ['user' => $target->id]))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('users/Show')
                ->where('profile.name', 'Target User')
                ->where('series.data.0.name', 'Target Collected')
            );
    }

    public function test_user_profile_can_filter_to_completed_series_only(): void
    {
        $viewer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();

        $completedSeries = Series::factory()->create([
            'name' => 'Completed Official',
            'custom' => false,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        $incompleteSeries = Series::factory()->create([
            'name' => 'Incomplete Official',
            'custom' => false,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        $completedClippers = Clipper::factory()->count(4)->create([
            'series_id' => $completedSeries->id,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        $incompleteClippers = Clipper::factory()->count(3)->create([
            'series_id' => $incompleteSeries->id,
            'requested_by' => $admin->id,
            'accepted_by' => $admin->id,
        ]);

        foreach ($completedClippers as $clipper) {
            $target->myCollection()->create(['clipper_id' => $clipper->id]);
        }

        foreach ($incompleteClippers as $clipper) {
            $target->myCollection()->create(['clipper_id' => $clipper->id]);
        }

        $this->actingAs($viewer)
            ->get(route('users.show', ['user' => $target->id, 'filter' => 'completed']))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('users/Show')
                ->where('filters.filter', 'completed')
                ->where('series.data.0.name', 'Completed Official')
                ->missing('series.data.1')
            );
    }
}
