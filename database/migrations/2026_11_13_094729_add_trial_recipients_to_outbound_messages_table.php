<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trial links are not guest invitations, so the log needs a recipient without an event.
     */
    public function up(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id')->nullable()->change();
            $table->string('recipient_name')->nullable()->after('guest_id');
            $table->string('recipient_address')->nullable()->after('recipient_name');
        });

        $smsReason = 'The SMS gateway is not set up.';
        $mailReason = config('mail.default') === 'log'
            ? 'Email is only saved in the application log.'
            : null;

        DB::table('demo_requests')
            ->whereNotNull('trial_granted_at')
            ->orderBy('id')
            ->each(function (object $demo) use ($smsReason, $mailReason): void {
                $when = $demo->trial_granted_at;
                $body = 'Your '.($demo->trial_days ?? 0).'-day Jumuika Pass trial is ready.';

                DB::table('outbound_messages')->insert([
                    [
                        'event_id' => null,
                        'guest_id' => null,
                        'recipient_name' => $demo->name,
                        'recipient_address' => $demo->phone,
                        'channel' => 'sms',
                        'purpose' => 'trial',
                        'body' => $body,
                        'status' => 'failed',
                        'failure_reason' => $smsReason,
                        'cost' => 0,
                        'covered_by_package' => 0,
                        'sent_at' => null,
                        'created_at' => $when,
                        'updated_at' => $when,
                    ],
                    [
                        'event_id' => null,
                        'guest_id' => null,
                        'recipient_name' => $demo->name,
                        'recipient_address' => $demo->email,
                        'channel' => 'email',
                        'purpose' => 'trial',
                        'body' => $body,
                        'status' => $mailReason === null ? 'delivered' : 'failed',
                        'failure_reason' => $mailReason,
                        'cost' => 0,
                        'covered_by_package' => 0,
                        'sent_at' => $mailReason === null ? $when : null,
                        'created_at' => $when,
                        'updated_at' => $when,
                    ],
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('outbound_messages')->where('purpose', 'trial')->delete();

        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropColumn(['recipient_name', 'recipient_address']);
            $table->unsignedBigInteger('event_id')->nullable(false)->change();
        });
    }
};
