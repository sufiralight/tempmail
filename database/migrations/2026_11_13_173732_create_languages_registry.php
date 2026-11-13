<?php

use App\Administration\LanguageRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 35)->unique();
            $table->string('name', 100);
            $table->string('native_name', 100);
            $table->string('direction', 3);
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order');
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('language_defaults', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->foreignId('language_id')->constrained('languages');
        });

        app(LanguageRegistry::class)->seed();
    }

    public function down(): void
    {
        Schema::dropIfExists('language_defaults');
        Schema::dropIfExists('languages');
    }
};
