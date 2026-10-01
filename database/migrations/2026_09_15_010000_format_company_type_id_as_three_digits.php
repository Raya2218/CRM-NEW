<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "company" DROP CONSTRAINT IF EXISTS "company_company_type_id_foreign"');
        DB::statement('ALTER TABLE "company" DROP CONSTRAINT IF EXISTS "company_company_type_id_fkey"');
        DB::statement('ALTER TABLE "company" DROP CONSTRAINT IF EXISTS "company_type_code_unique"');

        DB::statement('ALTER TABLE "company_type" ALTER COLUMN "company_type_id" TYPE varchar(3) USING LPAD("company_type_id"::text, 3, \'0\')');
        DB::statement('ALTER TABLE "company" ALTER COLUMN "company_type_id" TYPE varchar(3) USING LPAD("company_type_id"::text, 3, \'0\')');

        Schema::table('company', function (Blueprint $table) {
            $table->foreign('company_type_id')
                ->references('company_type_id')
                ->on('company_type')
                ->cascadeOnUpdate();
            $table->unique(['company_type_id', 'company_code'], 'company_type_code_unique');
        });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "company" DROP CONSTRAINT IF EXISTS "company_company_type_id_foreign"');
        DB::statement('ALTER TABLE "company" DROP CONSTRAINT IF EXISTS "company_company_type_id_fkey"');
        DB::statement('ALTER TABLE "company" DROP CONSTRAINT IF EXISTS "company_type_code_unique"');

        DB::statement('ALTER TABLE "company" ALTER COLUMN "company_type_id" TYPE integer USING "company_type_id"::integer');
        DB::statement('ALTER TABLE "company_type" ALTER COLUMN "company_type_id" TYPE integer USING "company_type_id"::integer');

        Schema::table('company', function (Blueprint $table) {
            $table->foreign('company_type_id')
                ->references('company_type_id')
                ->on('company_type')
                ->cascadeOnUpdate();
            $table->unique(['company_type_id', 'company_code'], 'company_type_code_unique');
        });
    }
};
