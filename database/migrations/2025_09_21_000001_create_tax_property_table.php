<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_property', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->integer('year');
            $table->string('code');
            $table->string('municipality')->nullable();
            $table->boolean('has_tax_on_homes')->default(false);
            $table->boolean('has_tax_on_companies')->default(false);
            $table->decimal('tax_home_permill', 6, 3)->nullable(); // permille, e.g. 3.500
            $table->decimal('tax_company_permill', 6, 3)->nullable(); // permille
            $table->decimal('deduction', 12, 2)->default(0); // bunnfradrag NOK
            $table->decimal('fortune_taxable_percent', 5, 2)->nullable(); // percentage of property value taxable for wealth tax, e.g. 70.00 for 70%

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'year', 'code']);
            $table->index(['country_code', 'year']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_property');
    }
};
