<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('font_assignments', function (Blueprint $table): void {
            $table->string('style', 6)->default('normal');
        });
    }

    public function down(): void
    {
        Schema::table('font_assignments', function (Blueprint $table): void {
            $table->dropColumn('style');
        });
    }
};
