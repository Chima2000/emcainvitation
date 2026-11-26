<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Demo clients created before the lead role were stored as hosts.
     */
    public function up(): void
    {
        $demoUserIds = DB::table('demo_requests')->whereNotNull('user_id')->pluck('user_id');
        $paidUserIds = DB::table('subscriptions')->distinct()->pluck('user_id');

        if ($demoUserIds->isEmpty()) {
            return;
        }

        $accounts = DB::table('users')
            ->where('role', 'host')
            ->whereIn('id', $demoUserIds);

        if ($paidUserIds->isNotEmpty()) {
            $accounts->whereNotIn('id', $paidUserIds);
        }

        $accounts->update(['role' => 'lead']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $demoUserIds = DB::table('demo_requests')->whereNotNull('user_id')->pluck('user_id');

        if ($demoUserIds->isEmpty()) {
            return;
        }

        DB::table('users')
            ->where('role', 'lead')
            ->whereIn('id', $demoUserIds)
            ->update(['role' => 'host']);
    }
};
