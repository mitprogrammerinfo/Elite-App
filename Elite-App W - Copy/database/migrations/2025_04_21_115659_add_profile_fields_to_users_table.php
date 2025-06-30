<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('first_name')->nullable()->after('name');
        $table->string('last_name')->nullable()->after('first_name');
        $table->string('phone_number')->nullable()->after('email');
        $table->string('address_line_1')->nullable();
        $table->string('address_line_2')->nullable();
        $table->string('city')->nullable();
        $table->string('zip')->nullable();
        $table->string('state')->nullable();
        $table->string('provider')->nullable()->after('google_id'); // google, email
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
