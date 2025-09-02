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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 2)->unique()->comment('ISO 3166-1 alpha-2 code');
            $table->string('code3', 3)->unique()->comment('ISO 3166-1 alpha-3 code');
            $table->string('numeric_code', 3)->nullable()->comment('ISO 3166-1 numeric code');
            $table->string('capital')->nullable();
            $table->string('flag_emoji', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
