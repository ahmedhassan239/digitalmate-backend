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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('description')->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_keywords')->nullable();
            $table->json('seo_description')->nullable();
            $table->string('status')->nullable();
            $table->string('robots')->nullable();
            $table->json('project_ids')->nullable(); // Ensure project_ids is defined as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
