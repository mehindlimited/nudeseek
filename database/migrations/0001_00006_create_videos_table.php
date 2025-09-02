<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('code')->unique();
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('file_hash')->nullable();
            $table->string('main_dir')->nullable();
            $table->integer('main_thumbnail')->nullable();
            $table->integer('thumbnails_count')->nullable();
            $table->timestamp('published_at');
            $table->enum('status', ['draft', 'encoding', 'published', 'deleted', 'inactive', 'hidden', 'active'])->default('draft');
            $table->enum('access_type', ['public', 'private', 'unlisted'])->default('public');
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('target_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
