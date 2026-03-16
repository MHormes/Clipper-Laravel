<?php

test('web app manifest is accessible with install metadata', function () {
    $response = $this->get(route('manifest'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/manifest+json');
    $response->assertJson([
        'name' => 'Clipper-MS',
        'short_name' => 'Clipper-MS',
        'start_url' => '/dashboard',
        'display' => 'standalone',
    ]);
    $response->assertJsonPath('icons.0.src', '/pwa-192x192.png');
    $response->assertJsonPath('icons.1.src', '/pwa-512x512.png');
});

test('application shell references the web app manifest', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('rel="manifest"', false);
    $response->assertSee(route('manifest'), false);
});
