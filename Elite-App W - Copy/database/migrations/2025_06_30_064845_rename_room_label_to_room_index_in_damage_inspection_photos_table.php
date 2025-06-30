<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('damage_inspection_photos', function (Blueprint $table) {
            $table->renameColumn('room_label', 'room_index');
        });
    }

    public function down(): void
    {
        Schema::table('damage_inspection_photos', function (Blueprint $table) {
            $table->renameColumn('room_index', 'room_label');
        });
    }
};
