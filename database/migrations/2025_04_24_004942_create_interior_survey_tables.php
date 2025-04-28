<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Static tables (manually populated)
        Schema::create('interior_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    
        Schema::create('interior_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('interior_categories');
            $table->string('name');
            $table->timestamps();
        });
    
        // Dynamic survey tables
        Schema::create('survey_int_cats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('interior_categories');
            $table->timestamps();
        });
    
        Schema::create('survey_int_cat_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_int_cat_id')->constrained('survey_int_cats')->onDelete('cascade');
            $table->string('image_path');
            $table->timestamps();
        });
    
        Schema::create('survey_int_cat_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_int_cat_id')->constrained('survey_int_cats')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('interior_features');
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('survey_int_cat_features');
        Schema::dropIfExists('survey_int_cat_images');
        Schema::dropIfExists('survey_int_cats');
        Schema::dropIfExists('interior_features');
        Schema::dropIfExists('interior_categories');
    }};