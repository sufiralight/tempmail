<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('storage_key')->unique();
            $table->string('filename');
            $table->string('mime', 24);
            $table->string('extension', 4);
            $table->unsignedBigInteger('bytes');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->boolean('thumbnail')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('branding_assets', function (Blueprint $table): void {
            $table->string('slot', 24)->primary();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branding_assets');
        Schema::dropIfExists('media');
    }
};
