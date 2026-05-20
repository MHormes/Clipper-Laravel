<?php

namespace App\Http\Controllers\Settings;

use App\Enums\EmailNotificationCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $preferences = $user->emailPreferencesWithDefaults();

        $categories = array_map(
            fn(EmailNotificationCategory $cat) => [
                'key'         => $cat->value,
                'label'       => $cat->label(),
                'description' => $cat->description(),
                'enabled'     => $preferences[$cat->value] ?? true,
                'recipient'   => $cat->recipient(),
            ],
            EmailNotificationCategory::forRole($user->role)
        );

        return Inertia::render('settings/Notifications', [
            'categories' => $categories,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $allowedKeys = array_map(
            fn(EmailNotificationCategory $c) => $c->value,
            EmailNotificationCategory::forRole($user->role)
        );

        // Unchecked checkboxes are not submitted — fill all keys as false first, then overlay submitted values
        $preferences = array_merge(
            array_fill_keys($allowedKeys, false),
            array_map(fn($v) => (bool) $v, $request->only($allowedKeys))
        );

        $user->update(['email_preferences' => $preferences]);

        return back();
    }
}
