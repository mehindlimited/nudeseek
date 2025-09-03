<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Target;
use Illuminate\Http\Request;

class CategoryApiController extends Controller
{
    /**
     * Get category by legacy value
     */
    public function getByLegacy($legacy)
    {
        try {
            $category = Category::where('legacy', $legacy)->first();

            if (!$category) {
                \Log::warning('Category not found for legacy:', ['legacy' => $legacy]);
                return response()->json([
                    'error' => 'Category not found',
                    'legacy' => $legacy
                ], 404);
            }

            \Log::info('Category found by legacy:', [
                'legacy' => $legacy,
                'category_id' => $category->id,
                'target_id' => $category->target_id
            ]);

            // Load target relationship for additional info
            $category->load('target');

            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
                'target_id' => $category->target_id,
                'legacy' => $category->legacy,
                'slug' => $category->slug ?? null,
                'description' => $category->description ?? null,
                'target' => [
                    'id' => $category->target->id,
                    'name' => $category->target->name
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting category by legacy:', [
                'legacy' => $legacy,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to get category'
            ], 500);
        }
    }

    /**
     * Get all categories for a specific target
     */
    public function getCategoriesForTarget($target_id)
    {
        try {
            $categories = Category::where('target_id', $target_id)
                ->orderBy('name')
                ->get(['id', 'name', 'target_id', 'legacy', 'slug']);

            if ($categories->isEmpty()) {
                \Log::warning('No categories found for target:', ['target_id' => $target_id]);
                return response()->json([
                    'error' => 'No categories found for target',
                    'target_id' => $target_id
                ], 404);
            }

            return response()->json([
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'target_id' => $category->target_id,
                        'legacy' => $category->legacy,
                        'slug' => $category->slug
                    ];
                }),
                'count' => $categories->count(),
                'target_id' => $target_id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting categories for target:', [
                'target_id' => $target_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get categories'
            ], 500);
        }
    }

    /**
     * Get all targets with their category counts
     */
    public function getTargetsWithCategoryCounts()
    {
        try {
            $targets = Target::withCount('categories')
                ->orderBy('id')
                ->get(['id', 'name']);

            return response()->json([
                'targets' => $targets->map(function ($target) {
                    return [
                        'id' => $target->id,
                        'name' => $target->name,
                        'categories_count' => $target->categories_count
                    ];
                }),
                'count' => $targets->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting targets with category counts:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get targets'
            ], 500);
        }
    }

    /**
     * Search categories by name or legacy value
     */
    public function searchCategories(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $target_id = $request->get('target_id');
            $limit = min($request->get('limit', 20), 100);

            $query = Category::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('legacy', 'like', "%{$search}%");
                });
            }

            if ($target_id) {
                $query->where('target_id', $target_id);
            }

            $categories = $query->with('target')
                ->orderBy('name')
                ->limit($limit)
                ->get();

            return response()->json([
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'target_id' => $category->target_id,
                        'legacy' => $category->legacy,
                        'slug' => $category->slug,
                        'target_name' => $category->target->name ?? null
                    ];
                }),
                'count' => $categories->count(),
                'search' => $search,
                'target_id' => $target_id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error searching categories:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to search categories'
            ], 500);
        }
    }

    /**
     * Get category mapping statistics (useful for debugging legacy imports)
     */
    public function getCategoryMappingStats()
    {
        try {
            $stats = [
                'total_categories' => Category::count(),
                'categories_with_legacy' => Category::whereNotNull('legacy')->count(),
                'categories_without_legacy' => Category::whereNull('legacy')->count(),
                'by_target' => []
            ];

            $targets = Target::withCount([
                'categories',
                'categories as categories_with_legacy_count' => function ($query) {
                    $query->whereNotNull('legacy');
                }
            ])->get();

            foreach ($targets as $target) {
                $stats['by_target'][] = [
                    'target_id' => $target->id,
                    'target_name' => $target->name,
                    'total_categories' => $target->categories_count,
                    'with_legacy' => $target->categories_with_legacy_count,
                    'without_legacy' => $target->categories_count - $target->categories_with_legacy_count
                ];
            }

            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('Error getting category mapping stats:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Get random category for target (useful for testing)
     */
    public function getRandomForTarget($target_id)
    {
        try {
            $category = Category::where('target_id', $target_id)
                ->inRandomOrder()
                ->first();

            if (!$category) {
                return response()->json([
                    'error' => 'No categories found for target',
                    'target_id' => $target_id
                ], 404);
            }

            $category->load('target');

            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
                'target_id' => $category->target_id,
                'legacy' => $category->legacy,
                'slug' => $category->slug,
                'target' => [
                    'id' => $category->target->id,
                    'name' => $category->target->name
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting random category for target:', [
                'target_id' => $target_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get random category'
            ], 500);
        }
    }

    /**
     * Bulk lookup categories by multiple legacy values
     */
    public function bulkLookupByLegacy(Request $request)
    {
        try {
            $validated = $request->validate([
                'legacy_values' => 'required|array|max:100',
                'legacy_values.*' => 'required|string'
            ]);

            $legacyValues = $validated['legacy_values'];

            $categories = Category::whereIn('legacy', $legacyValues)
                ->with('target')
                ->get();

            $found = [];
            $notFound = [];

            foreach ($legacyValues as $legacy) {
                $category = $categories->where('legacy', $legacy)->first();

                if ($category) {
                    $found[] = [
                        'legacy' => $legacy,
                        'category' => [
                            'id' => $category->id,
                            'name' => $category->name,
                            'target_id' => $category->target_id,
                            'legacy' => $category->legacy,
                            'target' => [
                                'id' => $category->target->id,
                                'name' => $category->target->name
                            ]
                        ]
                    ];
                } else {
                    $notFound[] = $legacy;
                }
            }

            return response()->json([
                'found' => $found,
                'not_found' => $notFound,
                'found_count' => count($found),
                'not_found_count' => count($notFound),
                'total_requested' => count($legacyValues)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in bulk category lookup:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to perform bulk lookup'
            ], 500);
        }
    }

    /**
     * Create or update category with legacy mapping (useful for migration)
     */
    public function createOrUpdateWithLegacy(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'target_id' => 'required|integer|exists:targets,id',
                'legacy' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:65535'
            ]);

            // Check if category with this legacy already exists
            $existingCategory = Category::where('legacy', $validated['legacy'])->first();

            if ($existingCategory) {
                // Update existing category
                $existingCategory->update([
                    'name' => $validated['name'],
                    'target_id' => $validated['target_id'],
                    'slug' => $validated['slug'] ?? \Str::slug($validated['name']),
                    'description' => $validated['description'] ?? null
                ]);

                $category = $existingCategory;
                $action = 'updated';
            } else {
                // Create new category
                $category = Category::create([
                    'name' => $validated['name'],
                    'target_id' => $validated['target_id'],
                    'legacy' => $validated['legacy'],
                    'slug' => $validated['slug'] ?? \Str::slug($validated['name']),
                    'description' => $validated['description'] ?? null
                ]);

                $action = 'created';
            }

            $category->load('target');

            \Log::info("Category {$action} with legacy mapping:", [
                'category_id' => $category->id,
                'legacy' => $category->legacy,
                'action' => $action
            ]);

            return response()->json([
                'success' => true,
                'action' => $action,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'target_id' => $category->target_id,
                    'legacy' => $category->legacy,
                    'slug' => $category->slug,
                    'target' => [
                        'id' => $category->target->id,
                        'name' => $category->target->name
                    ]
                ]
            ], $action === 'created' ? 201 : 200);
        } catch (\Exception $e) {
            \Log::error('Error creating/updating category with legacy:', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to create/update category'
            ], 500);
        }
    }

    /**
     * Get categories without legacy mapping (useful for cleanup)
     */
    public function getCategoriesWithoutLegacy(Request $request)
    {
        try {
            $target_id = $request->get('target_id');
            $limit = min($request->get('limit', 20), 100);

            $query = Category::whereNull('legacy');

            if ($target_id) {
                $query->where('target_id', $target_id);
            }

            $categories = $query->with('target')
                ->orderBy('name')
                ->limit($limit)
                ->get();

            return response()->json([
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'target_id' => $category->target_id,
                        'slug' => $category->slug,
                        'target_name' => $category->target->name ?? null
                    ];
                }),
                'count' => $categories->count(),
                'target_id' => $target_id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting categories without legacy:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get categories'
            ], 500);
        }
    }
}
