<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->string('company_code', 4)->nullable()->after('company_type_id');
            $table->unique(['company_type_id', 'company_code'], 'company_type_code_unique');
        });

        $nextCodes = [];
        DB::table('company')
            ->select(['company_id', 'company_type_id'])
            ->whereNull('company_code')
            ->orderBy('company_type_id')
            ->orderBy('company_id')
            ->get()
            ->each(function ($company) use (&$nextCodes): void {
                $typeId = (int) $company->company_type_id;
                $nextCodes[$typeId] = ($nextCodes[$typeId] ?? 0) + 1;

                DB::table('company')
                    ->where('company_id', $company->company_id)
                    ->update(['company_code' => str_pad((string) $nextCodes[$typeId], 4, '0', STR_PAD_LEFT)]);
            });
    }

    public function down(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->dropUnique('company_type_code_unique');
            $table->dropColumn('company_code');
        });
    }
};
