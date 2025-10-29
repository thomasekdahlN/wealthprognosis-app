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
            $table->string('country_code', 2);
            $table->integer('year');
            $table->string('tax_type');
            $table->foreign('tax_type')->references('type')->on('tax_types')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('configuration')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'year', 'tax_type']);
            $table->index(['country_code', 'year']);
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
