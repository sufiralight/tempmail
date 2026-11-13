<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 16)->default('user')->index();
            $table->boolean('is_active')->default(true);
        });
        Schema::create('workspace_settings', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('display_name', 80)->nullable();
            $table->bigInteger('revision')->default(0);
        });
        DB::table('workspace_settings')->insert(['id' => 1]);
        Schema::create('administrative_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 40)->index();
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->text('metadata');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_audits');
        Schema::dropIfExists('workspace_settings');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
