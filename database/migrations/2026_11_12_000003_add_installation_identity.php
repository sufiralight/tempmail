<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_super_admin')->default(false);
        });

        Schema::create('workspace_installation', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->uuid('operation_id')->unique();
            $table->foreignId('initial_admin_id')->unique()->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_installation');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_super_admin');
        });
    }
};
