<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 16)->nullable()->unique()->after('email');
            $table->string('role')->default('host')->after('phone');
        });

        $sequence = 0;

        DB::table('users')->whereNull('phone')->orderBy('id')->lazyById()->each(function (object $user) use (&$sequence): void {
            $sequence++;

            DB::table('users')->where('id', $user->id)->update([
                'phone' => '+2556'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 16)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'role']);
        });
    }
};
