<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickname', 50)->nullable()->after('name');
        });

        $usedNicknames = [];

        DB::table('users')
            ->select(['id', 'name', 'first_name'])
            ->orderBy('id')
            ->each(function (object $user) use (&$usedNicknames): void {
                $source = trim((string) ($user->first_name ?: Str::before((string) $user->name, ' ')));
                $base = preg_replace('/[^\pL\pM\pN_]+/u', '', $source) ?: 'User';
                $base = Str::limit($base, 38, '');
                $nickname = $base;
                $suffix = 1;

                while (isset($usedNicknames[Str::lower($nickname)])) {
                    $suffix++;
                    $nickname = Str::limit($base, 38 - strlen((string) $suffix), '').$suffix;
                }

                $usedNicknames[Str::lower($nickname)] = true;
                DB::table('users')->where('id', $user->id)->update(['nickname' => $nickname]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('nickname');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nickname']);
            $table->dropColumn('nickname');
        });
    }
};
