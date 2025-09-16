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
        Schema::table('questions', function (Blueprint $table) {
            // Convert single image field to JSON array for multiple images
            $table->json('images')->nullable()->after('question');
            
            // Remove the old image and image_format fields
            $table->dropColumn(['image', 'image_format']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Restore the old image fields
            $table->string('image')->nullable()->after('question');
            $table->string('image_format', 10)->default('png')->after('image');
            
            // Remove the new images field
            $table->dropColumn('images');
        });
    }
};
