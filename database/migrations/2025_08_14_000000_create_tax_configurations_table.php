<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('country_code', 2);
            $table->integer('year');
            $table->string('tax_type');
            $table->string('description')->nullable();
            $table->decimal('income_tax_rate', 8, 4)->default(0);
            $table->decimal('realization_tax_rate', 8, 4)->default(0);
            $table->decimal('fortune_tax_rate', 8, 4)->default(0);
            $table->decimal('property_tax_rate', 8, 4)->default(0);
            $table->decimal('standard_deduction', 15, 2)->default(0);
            $table->decimal('fortune_tax_threshold_low', 15, 2)->default(0);
            $table->decimal('fortune_tax_threshold_high', 15, 2)->default(0);
            $table->decimal('fortune_tax_rate_low', 8, 4)->default(0);
            $table->decimal('fortune_tax_rate_high', 8, 4)->default(0);
            $table->decimal('tax_shield_rate', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('configuration_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'year', 'tax_type']);
            $table->index(['country_code', 'year']);
            $table->index(['user_id', 'team_id']);
            $table->index(['tax_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_configurations');
    }
};
