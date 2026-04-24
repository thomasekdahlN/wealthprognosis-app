<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('birth_year')->nullable();
            $table->integer('prognose_age')->nullable();
            $table->integer('pension_official_age')->nullable();
            $table->integer('pension_wish_age')->nullable();
            $table->integer('expected_death_age')->nullable();
            $table->integer('export_start_age')->nullable();
            $table->boolean('public')->default(false);
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->string('color')->nullable();
            $table->jsonb('tags')->nullable();
            $table->enum('risk_tolerance', ['conservative', 'moderate_conservative', 'moderate', 'moderate_aggressive', 'aggressive'])->default('moderate');
            $table->string('country', 5)->default('no');

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('teams', 'id')->name('asset_configurations_team_id_foreign')->onDelete('cascade');

            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->name('asset_configurations_created_by_foreign');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->name('asset_configurations_updated_by_foreign');
            $table->char('created_checksum', 64)->nullable();
            $table->char('updated_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_configurations');
    }
};
