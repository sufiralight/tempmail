<?php

use App\Administration\Typography;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('font_families', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('font_assignments', function (Blueprint $table): void {
            $table->foreignId('language_id')->constrained();
            $table->string('role', 12);
            $table->foreignId('font_family_id')->nullable()->constrained();
            $table->unsignedSmallInteger('weight');
            $table->string('fallback', 12);
            $table->primary(['language_id', 'role']);
        });

        app(Typography::class)->seed();
    }

    public function down(): void
    {
        Schema::dropIfExists('font_assignments');
        Schema::dropIfExists('font_families');
    }
};
