<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Laravel stores Postgres enums as varchar + CHECK, which ->change() cannot alter.
            DB::statement('ALTER TABLE feedbacks DROP CONSTRAINT IF EXISTS feedbacks_status_check');
            DB::statement("ALTER TABLE feedbacks ADD CONSTRAINT feedbacks_status_check CHECK (status IN ('pending', 'analyzed', 'failed', 'rejected'))");

            return;
        }

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->enum('status', ['pending', 'analyzed', 'failed', 'rejected'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('feedbacks')->where('status', 'rejected')->update(['status' => 'failed']);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE feedbacks DROP CONSTRAINT IF EXISTS feedbacks_status_check');
            DB::statement("ALTER TABLE feedbacks ADD CONSTRAINT feedbacks_status_check CHECK (status IN ('pending', 'analyzed', 'failed'))");

            return;
        }

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->enum('status', ['pending', 'analyzed', 'failed'])
                ->default('pending')
                ->change();
        });
    }
};
