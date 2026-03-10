<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper;
use App\Models\CollectedClipper;
use App\Models\User;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserDirectoryController extends Controller
{
    public function following(Request $request)
    {
        $search = (string) $request->string('search')->trim();
        $viewer = $request->user();

        $users = $viewer->following()
            ->select(['users.id', 'users.name', 'users.created_at'])
            ->withCount('myCollection')
            ->withCount([
                'requestedSeries as accepted_series_contributions_count' => fn($query) => $query->accepted(),
                'requestedClippers as accepted_clipper_contributions_count' => fn($query) => $query->accepted(),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereRaw('LOWER(users.name) LIKE ?', ['%' . strtolower($search) . '%']);
            })
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString();

        $users->setCollection($this->transformDirectoryUsers($users->getCollection()));

        return Inertia::render('users/Following', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $search = (string) $request->string('search')->trim();

        $users = User::query()
            ->select(['id', 'name', 'created_at'])
            ->where('id', '!=', $request->user()->id)
            ->withCount('myCollection')
            ->withCount([
                'requestedSeries as accepted_series_contributions_count' => fn($query) => $query->accepted(),
                'requestedClippers as accepted_clipper_contributions_count' => fn($query) => $query->accepted(),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $users->setCollection($this->transformDirectoryUsers($users->getCollection()));

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Request $request, User $user)
    {
        $search = (string) $request->string('search')->trim();
        $filter = $request->input('filter', 'all') === 'completed' ? 'completed' : 'all';

        $series = $this->getCollectedSeriesForUser($user, [
            'search' => $search,
            'filter' => $filter,
        ]);

        return Inertia::render('users/Show', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                ...$this->resolveProfileStats($user),
                'can_follow' => $request->user()->id !== $user->id,
                'is_following' => $request->user()->following()->where('followed_id', $user->id)->exists(),
            ],
            'series' => $series,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
        ]);
    }

    public function toggleFollow(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer->id === $user->id) {
            return back();
        }

        $isFollowing = $viewer->following()
            ->where('followed_id', $user->id)
            ->exists();

        if ($isFollowing) {
            $viewer->following()->detach($user->id);
        } else {
            $viewer->following()->attach($user->id);
        }

        return back();
    }

    /**
     * Count completed series for a set of users.
     * Official series are completed at 4+ collected accepted clippers.
     * Custom series are completed when all accepted clippers are collected.
     */
    private function resolveCompletedSeriesCounts(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $officialCompleted = DB::query()
            ->fromSub(
                CollectedClipper::query()
                    ->selectRaw('collected_clippers.user_id, clippers.series_id')
                    ->join('clippers', 'collected_clippers.clipper_id', '=', 'clippers.id')
                    ->join('series', 'clippers.series_id', '=', 'series.id')
                    ->whereIn('collected_clippers.user_id', $userIds)
                    ->whereNotNull('series.accepted_by')
                    ->where('series.custom', false)
                    ->whereNotNull('clippers.accepted_by')
                    ->groupBy('collected_clippers.user_id', 'clippers.series_id')
                    ->havingRaw('COUNT(DISTINCT clippers.id) >= 4'),
                'official_completed'
            )
            ->selectRaw('official_completed.user_id, COUNT(*) as completed_count')
            ->groupBy('official_completed.user_id')
            ->pluck('completed_count', 'user_id');

        $customSeriesAcceptedTotals = Clipper::query()
            ->selectRaw('clippers.series_id, COUNT(*) as accepted_total')
            ->join('series', 'clippers.series_id', '=', 'series.id')
            ->whereNotNull('series.accepted_by')
            ->where('series.custom', true)
            ->whereNotNull('clippers.accepted_by')
            ->groupBy('clippers.series_id');

        $customCompleted = DB::query()
            ->fromSub(
                CollectedClipper::query()
                    ->selectRaw('collected_clippers.user_id, clippers.series_id')
                    ->join('clippers', 'collected_clippers.clipper_id', '=', 'clippers.id')
                    ->join('series', 'clippers.series_id', '=', 'series.id')
                    ->joinSub($customSeriesAcceptedTotals, 'custom_totals', function ($join) {
                        $join->on('custom_totals.series_id', '=', 'series.id');
                    })
                    ->whereIn('collected_clippers.user_id', $userIds)
                    ->whereNotNull('series.accepted_by')
                    ->where('series.custom', true)
                    ->whereNotNull('clippers.accepted_by')
                    ->groupBy('collected_clippers.user_id', 'clippers.series_id', 'custom_totals.accepted_total')
                    ->havingRaw('COUNT(DISTINCT clippers.id) >= custom_totals.accepted_total'),
                'custom_completed'
            )
            ->selectRaw('custom_completed.user_id, COUNT(*) as completed_count')
            ->groupBy('custom_completed.user_id')
            ->pluck('completed_count', 'user_id');

        return collect($userIds)
            ->mapWithKeys(function (string $userId) use ($officialCompleted, $customCompleted) {
                $official = (int) ($officialCompleted[$userId] ?? 0);
                $custom = (int) ($customCompleted[$userId] ?? 0);

                return [$userId => $official + $custom];
            })
            ->all();
    }

    private function transformDirectoryUsers($users)
    {
        $completedByUserId = $this->resolveCompletedSeriesCounts(
            $users->pluck('id')->all()
        );

        return $users->map(function (User $user) use ($completedByUserId) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'created_at' => $user->created_at,
                'collected_clippers_count' => $user->my_collection_count,
                'completed_series_count' => $completedByUserId[$user->id] ?? 0,
                'contributions_count' => (int) $user->accepted_series_contributions_count + (int) $user->accepted_clipper_contributions_count,
            ];
        });
    }

    private function resolveProfileStats(User $user): array
    {
        $completed = $this->resolveCompletedSeriesCounts([$user->id]);

        $acceptedSeriesContributions = $user->requestedSeries()
            ->accepted()
            ->count();

        $acceptedClipperContributions = $user->requestedClippers()
            ->accepted()
            ->count();

        return [
            'collected_clippers_count' => $user->myCollection()->count(),
            'completed_series_count' => $completed[$user->id] ?? 0,
            'contributions_count' => $acceptedSeriesContributions + $acceptedClipperContributions,
            'following_count' => $user->following()->count(),
            'followers_count' => $user->followers()->count(),
        ];
    }

    private function getCollectedSeriesForUser(User $user, array $filters)
    {
        $query = Series::accepted()
            ->whereHas('clippers.collections', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount(['clippers' => fn($query) => $query->accepted()])
            ->withCount(['clippers as collected_clippers_count' => function ($query) use ($user) {
                $query->accepted()->whereHas('collections', function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id);
                });
            }]);

        if (!empty($filters['search'])) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($filters['search']) . '%']);
        }

        if (($filters['filter'] ?? 'all') === 'completed') {
            $query->where(function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('series.custom', false)
                        ->whereHas('clippers', function ($query) use ($user) {
                            $query->accepted()->whereHas('collections', fn($subQuery) => $subQuery->where('user_id', $user->id));
                        }, '>=', 4);
                })
                ->orWhere(function ($query) use ($user) {
                    $query->where('series.custom', true)
                        ->whereHas('clippers', fn($subQuery) => $subQuery->accepted())
                        ->whereDoesntHave('clippers', function ($subQuery) use ($user) {
                            $subQuery->accepted()->whereDoesntHave('collections', fn($innerQuery) => $innerQuery->where('user_id', $user->id));
                        });
                });
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
    }
}
