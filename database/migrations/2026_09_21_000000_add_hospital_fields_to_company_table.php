<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->string('mobile_phone')->nullable();
            $table->string('owner')->nullable();
            $table->text('kemkes_link')->nullable();
            $table->string('sp_number')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_phone',
                'owner',
                'kemkes_link',
                'sp_number',
                'latitude',
                'longitude',
            ]);
        });
    }
};
