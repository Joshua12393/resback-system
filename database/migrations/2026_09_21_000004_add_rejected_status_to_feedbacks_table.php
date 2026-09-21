<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->enum('status', ['pending', 'analyzed', 'failed', 'rejected'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('feedbacks')->where('status', 'rejected')->update(['status' => 'failed']);

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->enum('status', ['pending', 'analyzed', 'failed'])
                ->default('pending')
                ->change();
        });
    }
};
