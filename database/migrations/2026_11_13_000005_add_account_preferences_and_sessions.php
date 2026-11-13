<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('appearance', 8)->nullable();
            $table->string('interface_locale', 12)->nullable();
            $table->string('timezone', 64)->nullable();
        });

        Schema::create('account_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('digest', 64)->unique();
            $table->string('device', 32);
            $table->unsignedBigInteger('last_seen');
            $table->boolean('revoked')->default(false);
            $table->index(['user_id', 'last_seen']);
            $table->index('last_seen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_sessions');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['appearance', 'interface_locale', 'timezone']);
        });
    }
};
