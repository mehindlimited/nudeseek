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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'user', 'webmaster', 'creator'])->default('user');
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('sexual_orientation', ['heterosexual', 'bisexual', 'omosexual', 'asexual', 'other'])->nullable();
            $table->integer('profile_views')->default(0);
            $table->integer('videos_count')->default(0);
            $table->integer('albums_count')->default(0);
            $table->foreignID('country_id')->nullable()->constrained()->onDelete('set null');
            $table->string('city')->nullable();
            $table->date('birthdate')->nullable();
            $table->enum('relationship_status', ['single', 'in_a_relationship', 'married', 'complicated', 'open'])->nullable();
            $table->boolean('has_avatar')->default(false);
            $table->boolean('is_real')->default(true);
            $table->time('last_online_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
