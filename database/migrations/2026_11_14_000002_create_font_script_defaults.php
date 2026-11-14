<?php

use App\Administration\Typography;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('font_script_defaults', function (Blueprint $table): void {
            $table->string('script', 30)->primary();
            $table->foreignId('font_family_id')->constrained()->noActionOnDelete();
        });

        app(Typography::class)->seed();
    }

    public function down(): void
    {
        Schema::dropIfExists('font_script_defaults');
    }
};
