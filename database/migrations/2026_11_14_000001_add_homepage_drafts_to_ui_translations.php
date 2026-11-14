<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ui_translation_values', function (Blueprint $table): void {
            $table->text('draft_value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ui_translation_values', function (Blueprint $table): void {
            $table->dropColumn('draft_value');
        });
    }
};
