<?php

namespace Tests\Browser;

use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CatalogTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test that users can see accepted series in the catalog.
     */
    public function test_user_can_view_catalog()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $series = Series::factory()->create([
            'name' => 'Visual Series Test',
            'accepted_by' => $admin->id
        ]);

        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $series) {
            $browser->loginAs($user)
                    ->visit('/series')
                    ->waitForText('Visual Series Test')
                    ->assertSee('Visual Series Test')
                    ->clickLink('Visual Series Test') // Assuming the name is a link
                    ->assertPathIs('/series/' . $series->id);
        });
    }
}
