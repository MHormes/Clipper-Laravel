<?php

namespace App\Services;

use App\Enums\EmailNotificationCategory;
use App\Models\User;
use Illuminate\Notifications\Notification;

class EmailNotificationService
{
    public function notifyAdmins(EmailNotificationCategory $category, Notification $notification): void
    {
        User::where('role', 'admin')
            ->get()
            ->filter(fn(User $admin) => $admin->is_active && $admin->wantsEmailFor($category))
            ->each(fn(User $admin) => $admin->notify(clone $notification));
    }

    public function notifyUser(User $user, EmailNotificationCategory $category, Notification $notification): void
    {
        if ($user->is_active && $user->wantsEmailFor($category)) {
            $user->notify($notification);
        }
    }
}
