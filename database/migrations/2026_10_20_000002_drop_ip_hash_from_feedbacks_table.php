<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The hashed IP was never read and could be matched against network logs,
 * which undermines anonymous feedback. Existing values are discarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn('ip_hash');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->string('ip_hash', 64)->nullable()->after('content');
        });
    }
};
