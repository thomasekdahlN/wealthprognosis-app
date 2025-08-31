<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prognoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('code');
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->boolean('public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'code']);
            $table->index(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prognoses');
    }
};
