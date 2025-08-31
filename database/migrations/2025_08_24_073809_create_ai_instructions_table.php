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
        Schema::create('ai_instructions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->text('system_prompt');
            $table->text('user_prompt_template');
            $table->string('model')->default('gpt-4');
            $table->integer('max_tokens')->default(2000);
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Audit fields
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->string('created_checksum');
            $table->string('updated_checksum');
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'sort_order']);
            $table->index(['user_id', 'team_id']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_instructions');
    }
};
