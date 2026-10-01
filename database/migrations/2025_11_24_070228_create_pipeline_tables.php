<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pipeline_stages')) {
            Schema::create('pipeline_stages', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pipelines')) {
            Schema::create('pipelines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stage_id')->constrained('pipeline_stages')->onDelete('cascade');
                $table->string('customer_name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('description')->nullable();
                $table->decimal('value', 15, 2)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pipelines');
        Schema::dropIfExists('pipeline_stages');
    }
};
