<?php

use App\Support\FeedbackSubmitter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace feedbacks.user_id with a keyed hash so feedback cannot be joined to
 * accounts. The original link is not recoverable after this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->string('submitter_hash', 64)->nullable()->after('id')->index();
        });

        DB::table('feedbacks')
            ->whereNotNull('user_id')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->each(function (object $feedback): void {
                DB::table('feedbacks')
                    ->where('id', $feedback->id)
                    ->update(['submitter_hash' => FeedbackSubmitter::hashFor((int) $feedback->user_id)]);
            });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }

    public function down(): void
    {
        // The account link cannot be restored from a hash; old rows stay unlinked.
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropIndex(['submitter_hash']);
            $table->dropColumn('submitter_hash');
        });
    }
};
