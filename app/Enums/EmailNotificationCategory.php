<?php

namespace App\Enums;

enum EmailNotificationCategory: string
{
    case NewSeriesRequest  = 'new_series_request';
    case NewClipperRequest = 'new_clipper_request';
    case SeriesAccepted    = 'series_request_accepted';
    case SeriesDeclined    = 'series_request_declined';
    case ClipperAccepted   = 'clipper_request_accepted';
    case ClipperDeclined   = 'clipper_request_declined';

    public function label(): string
    {
        return match($this) {
            self::NewSeriesRequest  => 'New Series Request',
            self::NewClipperRequest => 'New Clipper Request',
            self::SeriesAccepted    => 'Series Request Accepted',
            self::SeriesDeclined    => 'Series Request Declined',
            self::ClipperAccepted   => 'Clipper Request Accepted',
            self::ClipperDeclined   => 'Clipper Request Declined',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::NewSeriesRequest  => 'Receive an email when a user submits a new series request.',
            self::NewClipperRequest => 'Receive an email when a user requests clippers for an existing series.',
            self::SeriesAccepted    => 'Receive an email when your series request has been accepted.',
            self::SeriesDeclined    => 'Receive an email when your series request has been declined.',
            self::ClipperAccepted   => 'Receive an email when your clipper request has been accepted.',
            self::ClipperDeclined   => 'Receive an email when your clipper request has been declined.',
        };
    }

    /** 'admin' = only admins see/receive this. 'user' = sent to requester, visible to all. */
    public function recipient(): string
    {
        return match($this) {
            self::NewSeriesRequest,
            self::NewClipperRequest => 'admin',
            default                 => 'user',
        };
    }

    /** Returns categories visible/relevant for the given role. */
    public static function forRole(string $role): array
    {
        return array_values(array_filter(
            self::cases(),
            fn(self $c) => $role === 'admin' || $c->recipient() === 'user'
        ));
    }

    /** All categories keyed by value with default true — used for null preference fallback. */
    public static function defaults(): array
    {
        return array_combine(
            array_map(fn(self $c) => $c->value, self::cases()),
            array_fill(0, count(self::cases()), true)
        );
    }
}
