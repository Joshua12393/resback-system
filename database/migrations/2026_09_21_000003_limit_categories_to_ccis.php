<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->where('name', 'CCIS')
            ->update(['is_active' => true, 'updated_at' => now()]);

        DB::table('categories')
            ->where('name', '!=', 'CCIS')
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('categories')
            ->whereIn('name', [
                'COE',
                'CAS',
                'CBEA',
                'CHS',
                'CTE',
                'CIT',
                'CASAT',
                'CAFSD',
                'LIBRARY',
                'ADMIN',
                'TEATRO',
                'COVER COURT',
                'OVAL',
            ])
            ->update(['is_active' => true, 'updated_at' => now()]);
    }
};
