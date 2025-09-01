<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_years', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('asset_configuration_id')->nullable()->constrained('asset_configurations')->onDelete('cascade');

            $table->text('income_description')->nullable();
            $table->decimal('income_amount', 15, 2)->nullable();
            $table->enum('income_factor', ['monthly', 'yearly'])->nullable();
            $table->string('income_rule')->nullable();
            $table->string('income_transfer')->nullable();
            $table->string('income_source')->nullable();
            $table->string('income_changerate')->nullable();
            $table->boolean('income_repeat')->default(false);

            $table->text('expence_description')->nullable();
            $table->decimal('expence_amount', 15, 2)->nullable();
            $table->enum('expence_factor', ['monthly', 'yearly'])->nullable();
            $table->string('expence_rule')->nullable();
            $table->string('expence_transfer')->nullable();
            $table->string('expence_source')->nullable();
            $table->string('expence_changerate')->nullable();
            $table->boolean('expence_repeat')->default(false);

            $table->text('asset_description')->nullable();
            $table->decimal('asset_market_amount', 15, 2)->nullable();
            $table->decimal('asset_acquisition_amount', 15, 2)->nullable();
            $table->decimal('asset_equity_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_initial_amount', 15, 2)->nullable();
            $table->decimal('asset_paid_amount', 15, 2)->nullable();
            $table->string('asset_changerate')->nullable();
            $table->string('asset_rule')->nullable();
            $table->string('asset_transfer')->nullable();
            $table->string('asset_source')->nullable();
            $table->boolean('asset_repeat')->default(true);

            $table->text('mortgage_description')->nullable();
            $table->decimal('mortgage_amount', 15, 2)->nullable();
            $table->smallInteger('mortgage_years')->nullable();
            $table->string('mortgage_interest')->nullable();
            $table->smallInteger('mortgage_interest_only_years')->nullable();
            $table->string('mortgage_extra_downpayment_amount')->nullable();
            $table->decimal('mortgage_gebyr', 15, 2)->nullable();
            $table->decimal('mortgage_tax', 5, 2)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'year']);
            $table->index(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_years');
    }
};
