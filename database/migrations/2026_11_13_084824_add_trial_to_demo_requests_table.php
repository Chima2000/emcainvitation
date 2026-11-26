<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('trial_days')->nullable()->after('message');
            $table->dateTime('trial_starts_at')->nullable()->after('trial_days');
            $table->dateTime('trial_ends_at')->nullable()->after('trial_starts_at');
            $table->dateTime('trial_granted_at')->nullable()->after('trial_ends_at');
            $table->boolean('must_set_password')->default(false)->after('trial_granted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'trial_days',
                'trial_starts_at',
                'trial_ends_at',
                'trial_granted_at',
                'must_set_password',
            ]);
        });
    }
};
