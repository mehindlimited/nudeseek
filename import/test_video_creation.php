<?php
// Put this in a Laravel artisan command or run via tinker
// php artisan tinker
// Then paste this code

use App\Models\Video;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Target;
use App\Models\User;
use Illuminate\Support\Str;

// Test 1: Check if code is being preserved
echo "=== TEST 1: Code Preservation ===\n";
$testCode = 'test1234567890ab';
$video = Video::create([
    'title' => 'Test Video',
    'slug' => 'test-video',
    'code' => $testCode,
    'description' => 'Test description',
    'published_at' => now(),
    'status' => 'draft',
    'access_type' => 'public',
    'user_id' => 1, // Make sure this user exists
    'target_id' => 1, // Make sure this target exists
    'category_id' => null,
]);

echo "Original code: {$testCode}\n";
echo "Saved code: {$video->code}\n";
echo "Code preserved: " . ($testCode === $video->code ? 'YES' : 'NO') . "\n\n";

// Test 2: Check category assignment
echo "=== TEST 2: Category Assignment ===\n";
$category = Category::first();
if ($category) {
    $video->update(['category_id' => $category->id]);
    $video->refresh();
    echo "Category ID assigned: {$category->id}\n";
    echo "Video category_id: {$video->category_id}\n";
    echo "Category name: " . ($video->category?->name ?? 'NULL') . "\n";
} else {
    echo "No categories found in database\n";
}
echo "\n";

// Test 3: Check tag relationships
echo "=== TEST 3: Tag Relationships ===\n";
$tag1 = Tag::firstOrCreate(['name' => 'test-tag-1'], ['slug' => 'test-tag-1']);
$tag2 = Tag::firstOrCreate(['name' => 'test-tag-2'], ['slug' => 'test-tag-2']);

echo "Created tags: {$tag1->id} ({$tag1->name}), {$tag2->id} ({$tag2->name})\n";

$video->tags()->sync([$tag1->id, $tag2->id]);
$attachedTags = $video->tags()->get();

echo "Tags synced. Attached tags count: " . $attachedTags->count() . "\n";
foreach ($attachedTags as $tag) {
    echo "  - Tag ID {$tag->id}: {$tag->name}\n";
}
echo "\n";

// Test 4: Check pivot table
echo "=== TEST 4: Pivot Table Check ===\n";
$pivotRecords = DB::table('video_tag')->where('video_id', $video->id)->get();
echo "Pivot records count: " . $pivotRecords->count() . "\n";
foreach ($pivotRecords as $pivot) {
    echo "  - Video ID: {$pivot->video_id}, Tag ID: {$pivot->tag_id}\n";
}
echo "\n";

// Test 5: Model events check
echo "=== TEST 5: Model Events ===\n";
echo "Check if Video model has any boot() method or observers\n";
echo "Check if there are any model events (creating, created, updating, updated)\n";

// Clean up
$video->tags()->detach();
$video->delete();
$tag1->delete();
$tag2->delete();

echo "\n=== Tests Complete ===\n";
echo "Run this script to identify where the issue is occurring.\n";
