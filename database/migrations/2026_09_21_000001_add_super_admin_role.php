<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'admin', 'faculty', 'student') NOT NULL DEFAULT 'student'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'admin', 'faculty', 'student'])
                    ->default('student')
                    ->change();
            });
        }

        DB::table('users')
            ->where('email', 'admin@resback.local')
            ->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'faculty', 'student') NOT NULL DEFAULT 'student'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'faculty', 'student'])
                    ->default('student')
                    ->change();
            });
        }
    }
};
