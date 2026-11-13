<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->timestamp('purge_requested_at')->nullable();
        });

        DB::table('media')->whereNotNull('deleted_at')->update(['purge_requested_at' => DB::raw('deleted_at')]);
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn('purge_requested_at');
        });
    }
};
