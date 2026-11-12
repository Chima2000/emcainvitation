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
            $table->string('country', 100)->nullable();
            $table->string('region', 120)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('ward', 120)->nullable();
            $table->string('street', 180)->nullable();
            $table->string('address_line')->nullable();
            $table->timestamp('profile_completed_at')->nullable();
        });

        DB::table('users')
            ->whereNull('profile_completed_at')
            ->update(['profile_completed_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'region',
                'district',
                'ward',
                'street',
                'address_line',
                'profile_completed_at',
            ]);
        });
    }
};
