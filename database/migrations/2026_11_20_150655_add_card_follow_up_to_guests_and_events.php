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
        Schema::table('guests', function (Blueprint $table) {
            $table->string('table_name', 40)->nullable()->after('party_size');
            $table->timestamp('opened_at')->nullable()->after('sent_at');
            $table->string('door_token', 80)->nullable()->unique()->after('token');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('auto_reminder')->default(false)->after('is_public');
            $table->unsignedTinyInteger('reminder_days')->default(3)->after('auto_reminder');
            $table->timestamp('auto_reminder_sent_at')->nullable()->after('reminder_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique(['door_token']);
            $table->dropColumn(['table_name', 'opened_at', 'door_token']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['auto_reminder', 'reminder_days', 'auto_reminder_sent_at']);
        });
    }
};
