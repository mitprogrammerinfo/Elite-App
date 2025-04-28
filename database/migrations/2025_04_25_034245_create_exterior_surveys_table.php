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
        Schema::create('exterior_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    
        Schema::create('survey_ext_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->timestamps();
        });
        
        Schema::create('survey_ext_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('exterior_features');
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('survey_ext_features');
        Schema::dropIfExists('survey_ext_images');
        Schema::dropIfExists('exterior_features');
    }
};
