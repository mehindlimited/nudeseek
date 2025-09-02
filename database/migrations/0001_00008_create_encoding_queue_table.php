<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encoding_queue', function (Blueprint $table) {
            $table->id();
            $table->string('video_code')->unique();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('input_file_path'); // path to original file
            $table->string('output_file_path')->nullable(); // path to encoded file
            $table->json('thumbnail_paths')->nullable(); // array of 5 thumbnail paths
            $table->json('encoding_options')->nullable(); // resolution, bitrate, etc.
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('video_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encoding_queue');
    }
};
