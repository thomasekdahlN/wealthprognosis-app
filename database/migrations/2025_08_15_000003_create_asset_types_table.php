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
        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->default('gray');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_private')->default(false);
            $table->boolean('is_company')->default(false);
            $table->boolean('is_tax_optimized')->default(false);
            $table->boolean('is_liquid')->default(false);

            // New capabilities flags
            $table->boolean('can_generate_income')->default(false);
            $table->boolean('can_generate_expenses')->default(false);
            $table->boolean('can_have_mortgage')->default(false);
            $table->boolean('can_have_market_value')->default(false);

            // Default change rate suggestions (used to prefill Asset / AssetYear forms)
            $table->string('income_changerate')->nullable();
            $table->string('expence_changerate')->nullable();
            $table->string('asset_changerate')->nullable();

            $table->integer('sort_order')->default(0);
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories');
            $table->foreignId('tax_type_id')->nullable()->constrained('tax_types');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_types');
    }
};
