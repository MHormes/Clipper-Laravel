<?php

use App\Models\Series;
use App\Models\User;

test('sitemap.xml is accessible and contains accepted series', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $series = Series::factory()->create(['name' => 'Sitemap Series', 'accepted_by' => $admin->id]);

    $response = $this->get('/sitemap.xml');
    
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    $response->assertSee($series->id);
});

test('robots.txt is accessible', function () {
    $response = $this->get('/robots.txt');
    
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    $response->assertSee('User-agent: *');
    $response->assertSee('Disallow: /admin/');
});
