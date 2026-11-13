<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_translation_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier', 150)->unique();
            $table->text('source');
            $table->text('context')->nullable();
        });
        Schema::create('ui_translation_values', function (Blueprint $table): void {
            $table->foreignId('key_id')->constrained('ui_translation_keys');
            $table->foreignId('language_id')->constrained('languages');
            $table->text('value');
            $table->string('status', 16);
            $table->timestamps();
            $table->primary(['key_id', 'language_id']);
        });

        foreach (config('ui_translations') as $identifier => $source) {
            DB::table('ui_translation_keys')->insert(['identifier' => $identifier, 'source' => $source]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_translation_values');
        Schema::dropIfExists('ui_translation_keys');
    }
};
