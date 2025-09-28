<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_configuration_id')->nullable()->constrained('asset_configurations');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('asset_type');
            $table->string('group')->default('private');

            $table->string('tax_property', 50)->nullable();
            $table->string('tax_country', 5)->default('no');
            $table->boolean('is_active')->default(true);
            $table->boolean('debug')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->index(['asset_configuration_id', 'is_active']);
            $table->index(['asset_type', 'group']);
            $table->index(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
