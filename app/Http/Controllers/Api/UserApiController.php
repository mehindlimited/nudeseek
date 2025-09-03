<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    /**
     * Get a random user based on target_id and sexual_orientation criteria
     */
    public function getRandomForTarget($target_id)
    {
        try {
            $sexual_orientation = match ((int)$target_id) {
                1 => 3,  // If target_id = 1, sexual_orientation = 3
                2 => 1,  // If target_id = 2, sexual_orientation = 1
                default => 3  // Default fallback
            };

            \Log::info('Getting random user for target:', [
                'target_id' => $target_id,
                'sexual_orientation' => $sexual_orientation
            ]);

            $user = User::where('is_real', false)
                ->where('sexual_orientation', $sexual_orientation)
                ->inRandomOrder()
                ->first();

            if (!$user) {
                \Log::warning('No users found matching criteria:', [
                    'target_id' => $target_id,
                    'sexual_orientation' => $sexual_orientation,
                    'is_real' => false
                ]);

                return response()->json([
                    'error' => 'No users found matching criteria'
                ], 404);
            }

            \Log::info('Random user selected:', [
                'user_id' => $user->id,
                'username' => $user->username,
                'sexual_orientation' => $user->sexual_orientation,
                'target_id' => $target_id
            ]);

            return response()->json([
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'sexual_orientation' => $user->sexual_orientation,
                'target_id' => $target_id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting random user for target:', [
                'target_id' => $target_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to get random user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get multiple random users for a target (useful for batch processing)
     */
    public function getMultipleRandomForTarget($target_id, Request $request)
    {
        try {
            $count = $request->get('count', 1);
            $count = min($count, 50); // Limit to 50 users max

            $sexual_orientation = match ((int)$target_id) {
                1 => 3,
                2 => 1,
                default => 3
            };

            \Log::info('Getting multiple random users for target:', [
                'target_id' => $target_id,
                'sexual_orientation' => $sexual_orientation,
                'count' => $count
            ]);

            $users = User::where('is_real', false)
                ->where('sexual_orientation', $sexual_orientation)
                ->inRandomOrder()
                ->limit($count)
                ->get(['id', 'username', 'name', 'sexual_orientation']);

            if ($users->isEmpty()) {
                return response()->json([
                    'error' => 'No users found matching criteria'
                ], 404);
            }

            return response()->json([
                'users' => $users->map(function ($user) use ($target_id) {
                    return [
                        'id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                        'sexual_orientation' => $user->sexual_orientation,
                        'target_id' => $target_id
                    ];
                }),
                'count' => $users->count(),
                'target_id' => $target_id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting multiple random users:', [
                'target_id' => $target_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get users'
            ], 500);
        }
    }

    /**
     * Get user statistics for targets (useful for monitoring)
     */
    public function getUserStatsForTargets()
    {
        try {
            $stats = [];

            foreach ([1, 2] as $target_id) {
                $sexual_orientation = match ($target_id) {
                    1 => 3,
                    2 => 1,
                    default => 3
                };

                $count = User::where('is_real', false)
                    ->where('sexual_orientation', $sexual_orientation)
                    ->count();

                $stats[] = [
                    'target_id' => $target_id,
                    'sexual_orientation' => $sexual_orientation,
                    'user_count' => $count
                ];
            }

            return response()->json([
                'stats' => $stats,
                'total_fake_users' => User::where('is_real', false)->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting user stats:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get user statistics'
            ], 500);
        }
    }

    /**
     * Advanced user selection with additional criteria
     */
    public function getRandomForTargetAdvanced($target_id, Request $request)
    {
        try {
            $filters = $request->validate([
                'age_min' => 'nullable|integer|min:18',
                'age_max' => 'nullable|integer|max:100',
                'active_only' => 'nullable|boolean',
                'exclude_user_ids' => 'nullable|array',
                'exclude_user_ids.*' => 'integer'
            ]);

            $sexual_orientation = match ((int)$target_id) {
                1 => 3,
                2 => 1,
                default => 3
            };

            $query = User::where('is_real', false)
                ->where('sexual_orientation', $sexual_orientation);

            // Apply additional filters
            if (isset($filters['age_min'])) {
                $query->where('age', '>=', $filters['age_min']);
            }

            if (isset($filters['age_max'])) {
                $query->where('age', '<=', $filters['age_max']);
            }

            if (isset($filters['active_only']) && $filters['active_only']) {
                $query->where('last_activity', '>=', now()->subDays(30));
            }

            if (isset($filters['exclude_user_ids']) && !empty($filters['exclude_user_ids'])) {
                $query->whereNotIn('id', $filters['exclude_user_ids']);
            }

            $user = $query->inRandomOrder()->first();

            if (!$user) {
                return response()->json([
                    'error' => 'No users found matching criteria'
                ], 404);
            }

            return response()->json([
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'sexual_orientation' => $user->sexual_orientation,
                'age' => $user->age ?? null,
                'last_activity' => $user->last_activity ?? null,
                'target_id' => $target_id,
                'filters_applied' => $filters
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in advanced user selection:', [
                'target_id' => $target_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get user'
            ], 500);
        }
    }

    /**
     * Search users by username (for debugging/testing)
     */
    public function searchUsers(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $limit = min($request->get('limit', 10), 50);

            $users = User::where('username', 'like', "%{$search}%")
                ->where('is_real', false)
                ->limit($limit)
                ->get(['id', 'username', 'name', 'sexual_orientation']);

            return response()->json([
                'users' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                        'sexual_orientation' => $user->sexual_orientation
                    ];
                }),
                'count' => $users->count(),
                'search' => $search
            ]);
        } catch (\Exception $e) {
            \Log::error('Error searching users:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to search users'
            ], 500);
        }
    }
}
